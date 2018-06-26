'use strict';

angular.module('vkDemo.create', ['ngRoute', 'ngCookies']).config(['$routeProvider', function ($routeProvider) {
    $routeProvider.when('/create', {
        templateUrl: 'tickets/create.html',
        controller: 'createCtrl'
    });
}]).controller('createCtrl', function ($rootScope, $scope, $location, $cookies, User) {

    User.get().then((profile) => {
        if (!profile) {
            $location.path('/login');
            return false;
        }

        $scope.profile = profile;
    });

    $scope.submit = function (id, $event) {
        $scope.model.error = null;
        return User.request({
            method: 'order.create',
            token: $cookies.get('token'),
            title: $scope.model.title,
            description: $scope.model.description,
            cost: $scope.model.cost * 100,
        }).then((json) => {
            if (json.meta.code === 200 && json.order_id) {
                $location.path('/tickets/' + json.order_id);
                console.log('/tickets/' + json.order_id);
                $rootScope.$apply();
            } else {
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
