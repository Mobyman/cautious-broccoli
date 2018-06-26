'use strict';

angular.module('vkDemo.register', ['ngRoute'])

    .config(['$routeProvider', function ($routeProvider) {
        $routeProvider.when('/register', {
            templateUrl: 'register/register.html',
            controller: 'registerCtrl'
        });
    }])

    .controller('registerCtrl', function ($scope, $rootScope, $cookies, $location, User) {
        $scope.model = {};

        $scope.submit = function () {
            $scope.model.error = null;
            User.request({
                method: 'user.register',
                login: $scope.model.login,
                password: $scope.model.password,
                type: Number($scope.model.type)
            })
            .then((json) => {
                if (json.meta.code === 200 && json.status) {
                    $location.path('/login');
                    $rootScope.$apply();
                } else {
                    console.log(json);
                    if (json.message) {
                        $scope.model.error = json.message;
                        $scope.$apply('model');
                    }
                }

            }).catch((e) => {
                $scope.model.error = e.message || 'Error';
                $scope.$apply('model');
            });
        }
    });