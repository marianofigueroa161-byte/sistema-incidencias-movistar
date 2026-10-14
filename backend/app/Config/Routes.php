<?php
use CodeIgniter\Router\RouteCollection;
/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->group('api', static function ($routes) {
    // Autenticación
    $routes->post('login', 'Api\AuthController::login');
    $routes->get('sesion', 'Api\AuthController::sesion');
    $routes->post('logout', 'Api\AuthController::logout');
    // Incidencias
    $routes->get('incidencias', 'Api\IncidenciasController::index');
    $routes->post('incidencias', 'Api\IncidenciasController::crear');
    $routes->get('incidencias/resumen', 'Api\IncidenciasController::resumen');
    $routes->get('incidencias/(:num)/historial', 'Api\IncidenciasController::historial/$1');
    $routes->put('incidencias/(:num)/asignar', 'Api\IncidenciasController::asignarTecnico/$1');
    $routes->put('incidencias/(:num)/estado', 'Api\IncidenciasController::actualizarEstado/$1');
    $routes->get('categorias-incidencia', 'Api\IncidenciasController::categorias');
    $routes->get('servicios', 'Api\IncidenciasController::servicios');
    // Usuarios y clientes
    $routes->get('clientes', 'Api\UsuariosController::clientes');
    $routes->post('clientes', 'Api\UsuariosController::crearCliente');
    // Técnicos
    $routes->get('tecnicos', 'Api\TecnicosController::index');
    $routes->post('tecnicos', 'Api\TecnicosController::crear');
});