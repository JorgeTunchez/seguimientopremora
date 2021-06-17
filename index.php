<?php
require_once("core/core.php");
$objController = new login_controller();
$objController->runAjax();
$objController->drawContentController();

class login_controller{

  private $objModel;
  private $objView;
  
  public function __construct(){
    $this->objModel = new login_model();
    $this->objView = new login_view();
  }

  public function drawContentController(){
    $this->objView->drawContent(); 
  }

  public function runAjax(){
    $this->ajaxAuthUser();
  }

  public function ajaxAuthUser(){
    if( isset($_POST['loginUsername']) ){
      $strUser = isset($_POST['loginUsername']) ? trim($_POST['loginUsername']) : "";
      $strPassword = isset($_POST['loginPassword']) ? trim($_POST['loginPassword']) : "";
      $arrReturn = array();
      $boolRedirect = $this->objModel->redirect_dashboard($strUser,$strPassword);
      if( $boolRedirect ){
        $arrReturn["boolAuthRedirect"] = "Y";
      }else{
        $arrReturn["boolAuthRedirect"] = "N";
      }
      print json_encode($arrReturn);
      exit();
    }
  }

}

class login_model{

  public function redirect_dashboard($username, $password){
      $boolRedirect = auth_user($username, $password);
      return $boolRedirect;
  }

}

class login_view{

  private $objModel;

	public function __construct(){
    $this->objModel = new login_model();
  }

  public function drawContent(){
    ?>
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <title>Premora Agencias</title>
      <!-- Tell the browser to be responsive to screen width -->
      <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
      <!-- Bootstrap 3.3.7 -->
      <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
      <!-- Font Awesome -->
      <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
      <!-- Ionicons -->
      <link rel="stylesheet" href="bower_components/Ionicons/css/ionicons.min.css">
      <!-- Theme style -->
      <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
      <!-- Material Design -->
      <link rel="stylesheet" href="dist/css/bootstrap-material-design.min.css">
      <link rel="stylesheet" href="dist/css/ripples.min.css">
      <link rel="stylesheet" href="dist/css/MaterialAdminLTE.min.css">
     
      <!-- Google Font -->
      <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
      <style>
      .centrar
        {
            position: absolute;
            /*nos posicionamos en el centro del navegador*/
            top:50%;
            left:50%;
            float: none;
            /*determinamos una anchura*/
            width:400px;
            /*indicamos que el margen izquierdo, es la mitad de la anchura*/
            margin-left:-130px;
            /*determinamos una altura*/
            height:300px;
            /*indicamos que el margen superior, es la mitad de la altura*/
            margin-top:-150px;
            padding:5px;
            z-index: 1;
        }
      </style>
      <!-- jQuery 3 -->
    <script src="bower_components/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap 3.3.7 -->
    <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- Material Design -->
    <script src="dist/js/material.min.js"></script>
    <script src="dist/js/ripples.min.js"></script>
    
    </head>
    <body class="hold-transition login-page">
    <div class="login-box">
      <div class="login-logo">
        Premora Agencias
      </div>
      <!-- /.login-logo -->
      <div class="login-box-body">
        <div id="divShowLoadingGeneralBig" style="display:none;" class='centrar'><img src="images/loading.gif" height="300px" width="300px"></div>
        <div id="frmLogin">
          <form method="POST" action="javascript:void(0);">
            <div class="form-group has-feedback">
            <input type="text" id="loginUsername" name="loginUsername" class="form-control" placeholder="Usuario"/>
              <span class="glyphicon glyphicon-user form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
              <input type="password"  id="loginPassword" name="loginPassword" class="form-control" placeholder="Contraseña"/>
              <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
            <div class="row">
              <!-- /.col -->
              <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                <button id="btnIniciarSesion" class="btn btn-primary btn-raised btn-block" onclick="checkForm()">Iniciar Sesión</button>
              </div>
              <!-- /.col -->
            </div>
          </form>
        </div>
      </div>
      <!-- /.login-box-body -->
    </div>
    <!-- /.login-box -->
    <script>
        $.material.init();

        $("#loginPassword").keypress(function(e) {
            if(e.which == 13) {
                checkForm();
            }
        });

        function checkForm(){
            var boolError = false;
            if( $("#loginUsername").val() == '' ){
                $("#loginUsername").css('background-color','#f4d0de');
                boolError = true;
            }else{
                $("#loginUsername").css('background-color','');
            }  

            if( $("#loginPassword").val() == '' ){
                $("#loginPassword").css('background-color','#f4d0de');
                boolError = true;
            }else{
                $("#loginPassword").css('background-color','');
            } 

            if( boolError == false){
                var objSerialized = $("#frmLogin").find("select, input").serialize();
                $.ajax({
                    url:"index.php",
                    data: objSerialized,
                    type:"post",
                    dataType: "json",
                    beforeSend: function() {
                      $("#divShowLoadingGeneralBig").css("z-index", 1050);
                      $("#divShowLoadingGeneralBig").show();
                      $("#btnIniciarSesion").prop('disabled', true);
                    },
                    success:function(data){
                      $("#btnIniciarSesion").prop('disabled', false);
                      if (data.boolAuthRedirect == "Y"){
                        $("#divShowLoadingGeneralBig").hide();
                        location.href = "dashboard.php";
                      }else{
                        $("#divShowLoadingGeneralBig").hide();
                        alert("Datos incorrectos y/o usuario inactivo");
                        $("#loginUsername").val('');
                        $("#loginPassword").val('');
                      }
                    }
                });
            }
        }
   
    
    </script>
    
    </body>
    </html>
    <?php
  }

}

?>



