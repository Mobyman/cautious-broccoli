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
        if (json.item) {
            $scope.model = json.item;
        } else {
            $scope.model.error = e.message || 'Error';
        }
        $scope.$apply('model');
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
        };

        nextPage() {
            if (this.busy) return;
            this.busy = true;

            let self = this;

            return User.request({
                method: 'order.list',
                token: $cookies.get('token'),
                page: self.page
            }).then((json) => {

                if (json.meta.code === 200) {
                    let items = json.items;
                    for (let i = 0; i < items.length; i++) {
                        self.items.push(items[i]);
                    }

                    ++self.page;
                    self.busy = false;
                } else {
                    self.items = [{id: 1}]
                }

                $scope.$apply('tickets');

            }).catch((e) => {
                console.error(e);
            });
        };
    }

    $scope.tickets = new Ticket();

    $scope.assign = function (id, $event) {
        return User.request({
            method: 'order.assign',
            order_id: id,
            token: $cookies.get('token'),
        }).then((json) => {
            $event.target.handled = json.status || false;
            $event.target.closest(".ticket").remove();
        }).catch((e) => {
            console.error(e);
        });
    }
});
