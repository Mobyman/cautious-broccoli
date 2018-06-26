'use strict';

let app = angular.module('vkDemo.list', ['ngRoute', 'ngCookies']);

app.config(['$routeProvider', function ($routeProvider) {
    $routeProvider.when('/tickets', {
        templateUrl: 'tickets/list.html',
        controller: 'listCtrl'
    });

    $routeProvider.when('/tickets/:ticket', {
        templateUrl: 'tickets/single.html',
        controller: 'singleCtrl'
    });

}]);

app.controller('singleCtrl', function ($routeParams, $location, $scope, $cookies, User) {
    $scope.id = $routeParams.ticket;


    User.request({
        method: 'order.get',
        token: $cookies.get('token'),
        id: $scope.id
    }).then((json) => {

        if (json.meta.code === 200) {
            if (json.item) {
                $scope.model = json.item;
            } else {
                $scope.model.error = e.message || 'Error';
            }

            $scope.$apply('model');
        } else {

            $scope.model = {};
            $scope.model.error = json.message;
            $scope.$apply('model');

            return null;
        }

    }).catch((e) => {
        console.error(e);
        $scope.model.error = e.message || 'Error';
    });

});
app.controller('listCtrl', function ($location, $scope, $cookies, User) {

    User.get().then((profile) => {
        if (!profile) {
            $location.path('/login');
            return false;
        }

        $scope.profile = profile;
    });

    class Ticket {
        constructor() {
            this.items = [];
            this.busy = false;
            this.page = 1;
            this.end = false;
        };

        nextPage() {
            if (this.busy) return;
            if (this.end) return;

            this.busy = true;

            let self = this;

            return User.request({
                method: 'order.list',
                token: $cookies.get('token'),
                page: self.page
            }).then((json) => {

                self.loaded = true;
                if (json.meta.code === 404) {
                    self.busy = false;
                    self.end = true;
                    if (!self.items) {
                        $scope.error = json.message;
                    }
                    $scope.$apply('tickets');
                    return;
                }

                if (json.meta.code === 200) {
                    let items = json.items;
                    for (let i = 0; i < items.length; i++) {
                        self.items.push(items[i]);
                    }

                    ++self.page;
                    self.busy = false;
                }

                $scope.$apply('tickets');

            }).catch((e) => {
                console.error(e);
            });
        };
    }

    $scope.tickets = new Ticket();

    $scope.assign = function (id, $event, $index) {
        return User.request({
            method: 'order.assign',
            order_id: id,
            token: $cookies.get('token'),
        }).then((json) => {

            $scope.tickets.items.splice($index, 1);

            if (json.meta.code !== 200 || !json.order_id) {
                let error = json.message;
                console.log('Произошла ошибка при удалении', error);
            }

            if (json.meta.code === 200 && json.order_id) {
                if ($scope.tickets.items.length === 1) {
                    --$scope.tickets.page;
                    $scope.tickets.nextPage();
                }

                return true;
            }


        }).catch((e) => {
            console.error(e);
        });
    }
});
