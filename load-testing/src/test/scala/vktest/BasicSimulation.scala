package vktest

import io.gatling.core.Predef._
import io.gatling.http.Predef._
import scala.concurrent.duration._

class BasicSimulation extends Simulation {

  val httpConf = http
    .baseURL("http://vkdemo.mobyman.org/api")
    .header("Content-Type", "application/json")

  val feeder = csv("userpasswords.csv").queue

  val scn = scenario("Register and login")
    .feed(feeder)
    .exec(
      http("user.register")
        .post("/")
        .body(StringBody("""{"method": "user.register","login":"${user}","password":"${password}","type":1}"""))
        .check(jsonPath("$..meta.code").ofType[Int].is(200)))
    .exec(
      http("user.login")
        .post("/")
        .body(StringBody("""{"method": "user.auth","login":"${user}","password":"${password}"}"""))
        .check(jsonPath("$..token").ofType[String].saveAs("token")))
    .exec(
      http("user.profile")
        .post("/")
        .body(StringBody("""{"method": "user.profile","token":"${token}"}"""))
        .check(jsonPath("$..meta.code").ofType[Int].is(200)))
    .exec(
      http("order.create")
        .post("/")
        .body(StringBody("""{"method": "order.create","token":"${token}","cost":10000, "title":"20ca1dec062f45663a33d60790151b4cda81b862af3fe158a33df888fd07782de98fbc58bb3e1705f0c942be3fd5a4d942e95ad62fa2f6dcaa70ca5f5d5a35b8ac368a4","description":"20ca1dec062f45663a33d60790151b4cda81b862af3fe158a33df888fd07782de98fbc58bb3e1705f0c942be3fd5a4d942e95ad62fa2f6dcaa70ca5f5d5a35b8ac368a49e8023368716a5f3716cc0c4256862d4292760ec3b2dcdc0e44096813a157abc40ce264cfb84e865d52516f625bb21ab90dfdb053d51aefb8ec78cb965f40b727fe0461bd868960790215f8046407d1f8c8e4c04e05076c8da1b7f0023b0f106e5ea2b5d053bf46888ddf9d2cdea16504429fa5eaf5215d85e0349a628a44d53d059adda36702175255e0e027491c8f57cf6ca9b50de9c9eec966bfb91b96f4ae98b6b44061214fd326f983ef45baf86eeaea158fc361e850d779d575c1f76fcb8c0730f5d7537816c0e845e3aaee6cc0085ba58f7991804364bd9a3cbb18a2ce69aabf9ab3ddb5aaace6f26987088b7cb52622cd21535061a065d652"}"""))
        .check(jsonPath("$..meta.code").ofType[Int].is(200))
        .check(jsonPath("$..order_id").ofType[String].saveAs("order_id")))
    .exec(
      http("order.get")
        .post("/")
        .body(StringBody("""{"method": "order.get","token":"${token}","id":"${order_id}"}"""))
        .check(jsonPath("$..meta.code").ofType[Int].is(200)))

  setUp(scn.inject(rampUsers(800) over (60 seconds)).protocols(httpConf))
}
