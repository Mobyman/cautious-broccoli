'use strict';

angular.module('vkDemo.login', ['ngRoute', 'ngCookies'])

    .config(['$routeProvider', function ($routeProvider) {
        $routeProvider.when('/login', {
            templateUrl: 'login/login.html',
            controller: 'loginCtrl'
        });
    }])

    .controller('loginCtrl', ['$location', '$scope', '$cookies', '$rootScope', 'User', function ($location, $scope, $cookies, $rootScope, User) {

        User.get().then((profile) => {
            if (profile) {
                $location.path('/list');
                $rootScope.$apply();
            }

            return true;
        });

        $scope.submit = function () {
            $scope.model.error = null;

            User.auth($scope.model.login, $scope.model.password)
                .then((token) => { return User.get(token); })
                .then((profile) => {
                    if (profile) {
                        $location.path('/list');
                        $rootScope.$apply();
                    }
                })
                .catch((e) => {
                    $scope.model.error = e.message || 'Error';
                    $scope.$apply('model');
                });
        }
    }]);