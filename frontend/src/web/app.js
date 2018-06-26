'use strict';

angular.module('vkDemo', [
    'ngRoute',
    'ngCookies',
    'infinite-scroll',
    'vkDemo.login',
    'vkDemo.register',
    'vkDemo.list',
    'vkDemo.create',
]).service('User', function ($rootScope, $cookies) {

    const apiURL = 'https://vkdemo.mobyman.org/api/';
    const PROFILE_TTL = 30;

    let self = this;

    this.request = function (body) {
        return fetch(apiURL, {
            method: 'POST',
            headers: new Headers({'Content-Type': 'application/json'}),
            body: JSON.stringify(body)
        }).then((res) => {
            return res.json();
        }).catch((e) => {
            console.error(e);

            return null;
        });
    };

    this.auth = function (login, password) {
        return this.request({
            method: 'user.auth',
            login: login,
            password: password,
        }).then((json) => {
            if (json.meta.code === 200 && json.token !== void 0) {
                $cookies.put('token', json.token);

                return Promise.resolve(json.token);
            } else {
                return Promise.reject(json);
            }
        });
    };


    this.set = function (profile) {
        if (profile) {
            window.localStorage.setItem('profile', JSON.stringify({
                data: profile,
                expired: new Date().getTime() + (1000 * PROFILE_TTL)
            }));
        } else {
            window.localStorage.removeItem('profile');
        }
        $rootScope.$broadcast('updateProfile', profile);
    };

    this.getToken = function () {
        return $cookies.get('token');
    };

    this.get = function (token = null) {

        let profile = JSON.parse(window.localStorage.getItem('profile'));
        let currentTs = new Date().getTime();

        if (profile && profile.expired && currentTs < profile.expired) {
            return Promise.resolve(profile.data);
        }

        token = token || self.getToken();
        if (token) {
            return this.request({
                method: 'user.profile',
                token: this.getToken(),
            }).then((json) => {
                console.log(json);
                if (json.meta.code === 200) {
                    self.set(json.profile);

                    return json.profile;
                }
            });
        }

        return Promise.resolve(false);
    };

}).controller('logoutCtrl', function ($location, $cookies, User) {
    $cookies.remove('token');
    User.set(null);

    $location.path('/login');
}).controller('topBarController', function ($scope, $location, User) {
    $scope.types = {
        1: 'Заказчик',
        2: 'Фрилансер'
    };
    User.get().then((profile) => {
        $scope.profile = profile;
    });

    $scope.$on('updateProfile', function (e, profile) {
        $scope.profile = profile;
    });

}).config(function ($locationProvider, $routeProvider) {
    $locationProvider.hashPrefix('!');

    $routeProvider.when('/logout', {
        template: ' ',
        controller: 'logoutCtrl'
    });

    $routeProvider.otherwise({redirectTo: '/tickets'});

});