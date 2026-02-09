<?php

use App\Core\Router;

/** @var Router $router */

$router->get('/', 'HomeController@index');

$router->get('/registro', 'AuthController@showRegister');
$router->post('/registro', 'AuthController@register');

$router->get('/horas/eliminar', 'HorasController@eliminar');
$router->get('/horas/registrar', 'HorasController@registrar');
$router->post('/horas/registrar', 'HorasController@registrar');

$router->get('/acceso', 'AuthController@showLogin');
$router->post('/acceso', 'AuthController@login');
$router->get('/salir', 'AuthController@logout');

$router->get('/admin-setup', 'AdminSetupController@index');
$router->post('/admin-setup', 'AdminSetupController@create');

$router->get('/gestion-tareas', 'TaskManagementController@index');
$router->post('/gestion-tareas/categorias/crear', 'TaskManagementController@storeCategory');
$router->post('/gestion-tareas/categorias/actualizar', 'TaskManagementController@updateCategory');
$router->post('/gestion-tareas/categorias/eliminar', 'TaskManagementController@deleteCategory');
$router->get('/gestion-tareas/categorias/tareas', 'TaskManagementController@categoryTasks');
$router->post('/gestion-tareas/clasificaciones/crear', 'TaskManagementController@storeClassification');
$router->post('/gestion-tareas/clasificaciones/actualizar', 'TaskManagementController@updateClassification');
$router->post('/gestion-tareas/clasificaciones/eliminar', 'TaskManagementController@deleteClassification');
$router->post('/gestion-tareas/categorias/cambiar-estado', 'TaskManagementController@toggleCategoryStatus');

$router->get('/tareas/gestion', 'TaskGestionController@index');
$router->get('/tareas/gestor', 'TaskController@gestor');
$router->post('/tareas/gestor/crear-actividad', 'TaskController@crearActividad');
$router->post('/tareas/gestor/clonar-actividad', 'TaskController@clonarActividad');
$router->post('/tareas/gestor/eliminar-actividad', 'TaskController@eliminarActividad');
$router->get('/tareas/actividad', 'TaskController@showActividad');
$router->post('/tareas/registrar-horas', 'TaskController@registrarHoras');
$router->get('/tareas/revision', 'TaskController@revision');
$router->get('/tareas/colaborador-tareas', 'TaskController@tareasColaborador');
$router->get('/tareas', 'TaskController@index');
$router->post('/tareas/crear', 'TaskController@store');
$router->get('/tareas/detalle', 'TaskController@show');
$router->get('/tareas/editar', 'TaskController@edit');
$router->post('/tareas/actualizar', 'TaskController@update');
$router->post('/tareas/bitacora', 'TaskController@addLog');
$router->post('/tareas/tiempo', 'TaskController@addTime');
$router->post('/tareas/tiempo/eliminar', 'TaskController@deleteTime');
$router->post('/tareas/estado', 'TaskController@advanceStatus');
$router->post('/tareas/terminar', 'TaskController@complete');
$router->post('/tareas/eliminar', 'TaskController@delete');
$router->get('/tareas/buscar-usuarios', 'TaskController@searchUsers');
$router->get('/tareas/buscar-categorias', 'TaskController@searchCategories');
$router->post('/tareas/categorias/copiar', 'TaskController@copyCategoryTasks');

$router->get('/admin/usuarios', 'Admin\\UserController@index');
$router->get('/admin/usuarios/nuevo', 'Admin\\UserController@create');
$router->post('/admin/usuarios/guardar', 'Admin\\UserController@store');
$router->get('/admin/usuarios/editar', 'Admin\\UserController@edit');
$router->post('/admin/usuarios/actualizar', 'Admin\\UserController@update');
$router->post('/admin/usuarios/desactivar', 'Admin\\UserController@deactivate');
$router->post('/admin/usuarios/activar', 'Admin\\UserController@activate');
$router->post('/admin/usuarios/eliminar', 'Admin\\UserController@delete');
$router->get('/admin/usuarios/buscar', 'Admin\\UserController@search');

$router->get('/admin/categorias', 'Admin\\ClassificationController@index');
$router->post('/admin/categorias/crear', 'Admin\\ClassificationController@store');
$router->post('/admin/categorias/actualizar', 'Admin\\ClassificationController@update');
$router->post('/admin/categorias/eliminar', 'Admin\\ClassificationController@delete');

$router->get('/admin/roles', 'Admin\\RoleController@index');
$router->post('/admin/roles/guardar', 'Admin\\RoleController@store');
$router->post('/admin/roles/actualizar', 'Admin\\RoleController@update');
$router->post('/admin/roles/eliminar', 'Admin\\RoleController@delete');

$router->get('/admin/equipos', 'Admin\\TeamController@index');
$router->post('/admin/equipos/crear', 'Admin\\TeamController@store');
$router->post('/admin/equipos/asignar-colaborador', 'Admin\\TeamController@assignMember');
$router->post('/admin/equipos/remover-colaborador', 'Admin\\TeamController@removeMember');
$router->get('/admin/equipos/buscar', 'Admin\\TeamController@search');
$router->get('/admin/equipos/detalle', 'Admin\\TeamController@show');
$router->post('/admin/equipos/eliminar', 'Admin\\TeamController@delete');
