package vktest

import io.gatling.core.Predef._
import io.gatling.http.Predef._
import scala.concurrent.duration._
import scala.util.Random

class BasicSimulation extends Simulation {

  val httpConf = http
    .baseURL("https://vkdemo.mobyman.org/api")
    .header("Content-Type", "application/json")

  val pageSize = 50;
  val users = 1200;

  val titleFeeder = Iterator.continually(
    Map(
      "title" -> Random.alphanumeric.take(10).mkString,
      "description" -> Random.alphanumeric.take(20).mkString
    )
  )

  val userFeeder = Iterator.continually(
    Map(
      "login" -> Random.alphanumeric.take(10).mkString,
      "password" -> Random.alphanumeric.take(10).mkString
    )
  )

  val typeFeeder = Iterator.continually(
    Map( "type" -> (if (Random.nextInt(100) > 60) 2 else 1) )
  )

  val pageFeeder = Iterator.continually(
    Map( "page" -> (1 + Random.nextInt(10)))
  )

  val offsetFeeder = Iterator.continually(
    Map( "offset" -> (1 + Random.nextInt(20)))
  )

  val scn = scenario("Basic")
    .feed(titleFeeder)
    .feed(userFeeder)
    .feed(typeFeeder)
    .feed(pageFeeder)
    .feed(offsetFeeder)
    .exec(
      http("user.register")
        .post("/")
        .body(StringBody("""{"method": "user.register","login":"${login}","password":"${password}","type":${type}}"""))
        .check(jsonPath("$..meta.code").ofType[Int].is(200)))
    .exec(
      http("user.login")
        .post("/")
        .body(StringBody("""{"method": "user.auth","login":"${login}","password":"${password}"}"""))
        .check(jsonPath("$..token").ofType[String].saveAs("token")))
    .exec(
      http("user.profile")
        .post("/")
        .body(StringBody("""{"method": "user.profile","token":"${token}"}"""))
        .check(jsonPath("$..meta.code").ofType[Int].is(200)))
    .doIfEquals("${type}", 1) {
      exec(
        http("order.create")
          .post("/")
          .body(StringBody("""{"method": "order.create","token":"${token}","cost":100, "title":"${title}","description":"${description}"}"""))
          .check(jsonPath("$..meta.code").ofType[Int].is(200))
          .check(jsonPath("$..order_id").ofType[String].saveAs("order_id")))
        .exec(
          http("order.get")
            .post("/")
            .body(StringBody("""{"method": "order.get","token":"${token}","id":"${order_id}"}"""))
            .check(jsonPath("$..meta.code").ofType[Int].is(200)))
    }
    .doIfEquals("${type}", 2) {
      exec(
        http("order.list")
          .post("/")
          .body(StringBody("""{"method": "order.list","token":"${token}","page":${page}}"""))
          .check(jsonPath("$..meta.code").ofType[Int].is(200))
          .check(jsonPath("$..items[*].id").findAll.transform(s => s(0)).saveAs("order_id")))
        .doIf(s => !s("order_id").asOption[String].isEmpty) {
          exec(
            http("order.assign")
              .post("/")
              .body(StringBody("""{"method": "order.assign","token":"${token}","order_id":${order_id}}"""))
              .check(jsonPath("$..meta.code").ofType[Int].is(200)))
        }

    }


  setUp(scn.inject(rampUsers(users) over (60 seconds)).protocols(httpConf))
}
