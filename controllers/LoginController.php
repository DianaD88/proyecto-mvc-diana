<?php

namespace Controllers;

use Classes\Email;
use Model\Usuario;
use MVC\Router;

class LoginController {


    public static function login(Router $router) {
        $alertas = [];

        if($_SERVER ['REQUEST_METHOD'] === 'POST') {
            //echo 'Desde Post';
            $auth = new usuario($_POST);
            $alertas = $auth-> validarLogin();
        
                
            if(empty($alertas)) {
                //echo 'El usuario proporciono correo y contraseña';
                //Comprobar que existe el usuario
                $usuario = Usuario::buscarPorCampo('email', $auth->email );

                if($usuario) {
                    //Verificar la contraseña
                    if($usuario->comprobarContrasenaAndVerificado($auth->password)) {
                        //Auntenticar el usuario
                        session_start();

                       // $_SESSION['id'] = $usuario->id;

                        //debuguear($_SESSION);

                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre . '' . $usuario->apellido;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;

                        //debuguear($_SESSION);

                        //Redireccionamiento

                        if ($usuario->admin ==1) {
                            $_SESSION['admin'] = $usuario->admin ?? null;
                            header('Location: /admin');
                        } else {
                            header('Location: /cliente');
                        }
                      
                   
                    }

                } else {
                    Usuario::setAlerta('error', 'Usuario no encontrado');
                }
            

            }

        }
        $alertas = Usuario::getAlertas();
        $router->render('auth/login',[
            'alertas' => $alertas
        ]);
    }

    public static function logout() {
        echo 'Desde logout';
    }

    public static function olvide(Router $router) {
        $alertas = [];


        if($_SERVER ['REQUEST_METHOD'] === 'POST') {
            $auth = new Usuario($_POST);
            $alertas = $auth->validarEmail();
            if (empty($alertas)) {
                $usuario = Usuario :: buscarPorCampo('email', $auth->email);
                if ($usuario && $usuario->confirmado == 1) {
                    //Generar un token
                    $usuario->crearToken();
                    $usuario->guardar();

                    //TODO Enviar emial
                    $email = new Email(
                        $usuario->email,
                        $usuario->nombre,
                        $usuario->token 
                    );
                    $email->enviarInstrucciones();

                    Usuario::setAlerta('exito', 'Revisar tu correo');
                } else {
                    Usuario::setAlerta('error', 'El usuario no existe o no esta confirmado');
                }

            }
        }
        $alertas = Usuario::getAlertas();
        $router->render('auth/olvide-password', [
            'alertas' => $alertas
        ]);

        $alertas = $auth->validarEmail();

        if(empty($alertas)) {
            $usuario = Usuario :: buscarPorCampo('email', $auth->email);
            //debuguear($usuario);
        }
    }
   
    public static function recuperar(Router $router) {

        $alertas = [];

        $error = false;

        $token = s($_GET['token']);

        //Buscar usuario por su token
        $usuario = Usuario::buscarPorCampo('token', $token);

        //debuguear($usuario);

        if(empty($usuario)) {
            Usuario::setAlerta('error', 'Token no valido');
            $error = true;
        }
        if($_SERVER['REQUEST_METHOD']=== 'POST') {

            //Leer el nuevo password y guardarlo 
            $password = new Usuario($_POST);
            $alertas = $password->validarPassword();
            
            if(empty($alertas)){
                $usuario->password = null;


                $usuario->password = $password->password;
                $usuario->hashPassword();
                $usuario->token =null;

                $resultado = $usuario->guardar();
                if($resultado) {
                    header('Location: /');
                }


                //debuguear($usuario);
            }
            
        }

        $alertas = Usuario::getAlertas();

        $router->render('auth/recuperar-password', [
            'alertas' => $alertas,
            'error' => $error
        ]);
        
    }

    public static function crear(Router $router) {

        $usuario = new Usuario;
        
        //Alertas vacias
        $alertas = [];

        if($_SERVER ['REQUEST_METHOD'] === 'POST') {

            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarNuevaCuenta();

            //Revisar que las alertas esten en vacio
            if(empty($alertas)) {
                //Verificar que el usuario no este registrado o no exista
                $resultado = $usuario->existeUsuario();

                //debuguear($usuario);

                if ($resultado->num_rows) {
                    $alertas = Usuario::getAlertas();
                } else {
                    //Hasear el password
                    $usuario->hashPassword();
                    
                    
                    //Gernerar un token único
                    $usuario->crearToken();
                    $usuario->guardar();

                    // TODO Enviar el email
                    $email = new Email(
                     $usuario->email,
                     $usuario->nombre,
                     $usuario->token
                    );
                    $email->enviarConfirmacion();
                    

                    //Crear el usuario.
                    $resultado = $usuario->guardar();
        
                    //debuguear($usuario);

                    if($resultado) {
                       header('Location: /mensaje');
                        
                    }
                    //debuguear($usuario);
                }

            }
        }


        $router->render('auth/crear-cuenta',[
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
        
    }

    public static function confirmar(Router $router) {
        $alertas = [];
        $token = s($_GET['token']);
        $usuario = Usuario::buscarPorCampo('token', $token);

        if(empty($usuario)) {
            // Mostrar mensaje de error
            Usuario::setAlerta('error', 'Token No Válido');
        } else {
            // Modificar a usuario confirmado
            $usuario->confirmado = "1";
            $usuario->token = null;
            $usuario->guardar();
            Usuario::setAlerta('exito', 'Cuenta Comprobada Correctamente');
        }
       
        // Obtener alertas
        $alertas = Usuario::getAlertas();

        // Renderizar la vista
        $router->render('auth/confirmar-cuenta', [
            'alertas' => $alertas
        ]);
    }

    public static function mensaje(Router $router) {

        $router->render('auth/mensaje');

       
    }
    
    public static function admin () {
        echo 'Desde admin';
    }

    public static function cliente() {
        echo 'Desde cliente';
    }    
    
}