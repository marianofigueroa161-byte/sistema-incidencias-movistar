<?php
use CodeIgniter\Router\RouteCollection;

$routes->get('/', 'Home::index');
$routes->group('api', static function ($routes) {
    $routes->post('login', 'Api\AuthController::login');
    $routes->group('', ['filter' => 'apiauth:ADMINISTRADOR,SUPERVISOR,CLIENTE'], static function ($routes) {
        $routes->post('incidencias', 'Api\IncidenciasController::crear');
    });
    $routes->group('', ['filter' => 'apiauth'], static function ($routes) {
        $routes->get('sesion', 'Api\AuthController::sesion');
        $routes->get('notificaciones', 'Api\NotificacionesController::index');
        $routes->put('notificaciones/(:num)/leer', 'Api\NotificacionesController::marcarLeida/$1');
        $routes->put('notificaciones/leer-todas', 'Api\NotificacionesController::marcarTodasLeidas');
        $routes->post('logout', 'Api\AuthController::logout');
        $routes->get('incidencias', 'Api\IncidenciasController::index');

        $routes->get('incidencias/resumen', 'Api\IncidenciasController::resumen');
        $routes->get('incidencias/(:num)/historial', 'Api\IncidenciasController::historial/$1');
        $routes->get('categorias-incidencia', 'Api\IncidenciasController::categorias');
        $routes->get('servicios', 'Api\IncidenciasController::servicios');
    });
    $routes->group('', ['filter' => 'apiauth:ADMINISTRADOR,SUPERVISOR'], static function ($routes) {
        $routes->put('incidencias/(:num)/asignar', 'Api\IncidenciasController::asignarTecnico/$1');
        $routes->get('clientes', 'Api\UsuariosController::clientes');
        $routes->get('tecnicos', 'Api\TecnicosController::index');
    });
    $routes->group('', ['filter' => 'apiauth:ADMINISTRADOR,SUPERVISOR,TECNICO'], static function ($routes) {
        $routes->put('incidencias/(:num)/estado', 'Api\IncidenciasController::actualizarEstado/$1');
    });
    $routes->group('', ['filter' => 'apiauth:ADMINISTRADOR'], static function ($routes) {
        $routes->post('clientes', 'Api\UsuariosController::crearCliente');
        $routes->post('tecnicos', 'Api\TecnicosController::crear');
    });
});
