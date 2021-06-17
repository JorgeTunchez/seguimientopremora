<?php
require_once("core/core.php");
error_reporting(E_ALL);
ini_set('display_errors',0);
session_start();
if ( isset($_SESSION['user_id']) ) {
  $strRolUserSession = getRolUserSession($_SESSION['user_id']);
  $intIDUserSession = getIDUserSession($_SESSION['user_id']);

  if( $strRolUserSession != '' ){
    $arrRolUser["ID"] = $intIDUserSession;
    $arrRolUser["NAME"] = $_SESSION['user_id'];

    if( $strRolUserSession == "master" ){
      $arrRolUser["MASTER"] = true;
    }elseif( $strRolUserSession == "normal" ){
      $arrRolUser["NORMAL"] = true;
      header("Location: dashboard.php");
    }elseif( $strRolUserSession == "mora" ){
      $arrRolUser["MORA"] = true;
      header("Location: dashboard.php");
    }
  }
}else{
  header("Location: index.php");
}

$objController = new usuarios_controller($arrRolUser);
$objController->process();
$objController->runAjax();
$objController->drawContentController();

class usuarios_controller{

  private $objModel;
  private $objView;
  private $arrRolUser;
  
  public function __construct($arrRolUser){
    $this->objModel = new usuarios_model();
    $this->objView = new usuarios_view($arrRolUser);
    $this->arrRolUser = $arrRolUser;
  }

  public function drawContentController(){
    $this->objView->drawContent(); 
  }

  public function runAjax(){
    $this->ajaxDestroySession();
  }

  public function ajaxDestroySession(){
    if( isset($_POST["destroSession"]) ){
      header("Content-Type: application/json;");
      session_destroy();
      $arrReturn["Correcto"] = "Y";
      print json_encode($arrReturn);
      exit();
    }
  }

  public function process(){
    reset($_POST);
    while( $arrTMP = each($_POST) ){
        $arrExplode = explode("_",$arrTMP['key']);
        if( $arrExplode[0] == "hdnUsuario"){
          $intUsuario = $arrExplode[1];
          $strAccion = isset($_POST["hdnUsuario_{$intUsuario}"]) ? trim($_POST["hdnUsuario_{$intUsuario}"]):'';
          
          $strNombre = isset($_POST["txtNombre_{$intUsuario}"]) ? trim($_POST["txtNombre_{$intUsuario}"]):'';
          $strPassword = isset($_POST["txtPassword_{$intUsuario}"]) ? trim($_POST["txtPassword_{$intUsuario}"]):'';
          $arrAgencias = isset($_POST["selectAgencia_{$intUsuario}"]) ? $_POST["selectAgencia_{$intUsuario}"]:'';
          $intActivo = isset($_POST["selectActivo_{$intUsuario}"]) ? trim($_POST["selectActivo_{$intUsuario}"]):'';
          $intTipoUsuario = isset($_POST["selectTipoUsuario_{$intUsuario}"]) ? trim($_POST["selectTipoUsuario_{$intUsuario}"]):'';

          if( $strAccion == "A" ){
            $intUltimo = $this->objModel->insertUsuario($strNombre, $strPassword, $intActivo, $intTipoUsuario, $this->arrRolUser["ID"]);
            $this->objModel->updateUsuarioAgencia($intUltimo, $arrAgencias, $this->arrRolUser["ID"]);
          }elseif( $strAccion == "D" ){
            $this->objModel->deleteUsuario($intUsuario);
          }elseif( $strAccion == "E" ){
            $this->objModel->updateUsuario($intUsuario, $strNombre, $strPassword, $intActivo, $intTipoUsuario, $this->arrRolUser["ID"]);
            $this->objModel->updateUsuarioAgencia($intUsuario, $arrAgencias, $this->arrRolUser["ID"]);
          }
        }
    }
}

}

class usuarios_model{

  public function getAgencias(){
    $conn = getConexion();
    $arrAgencias = array();
    $strQuery = "SELECT codigo, nombre FROM agencias ORDER BY nombre";
    $result = mysqli_query($conn, $strQuery);
    if( !empty($result) ){
        while($row = mysqli_fetch_assoc($result)) {
            $arrAgencias[$row["codigo"]]["NOMBRE"] = $row["nombre"];
        }
    }
    mysqli_close($conn);
    return $arrAgencias;
  }

  public function getAgenciasUsuario($intUsuario){
    if( $intUsuario>0 ){
        $arrAgencias = array();
        $conn = getConexion();
        $strQuery = "SELECT agencias.codigo, agencias.nombre 
                       FROM usuario_agencias 
                            INNER JOIN agencias ON usuario_agencias.agencia = agencias.codigo
                      WHERE usuario_agencias.usuario = {$intUsuario}";
        $result = mysqli_query($conn, $strQuery);
        if( !empty($result) ){
            while($row = mysqli_fetch_assoc($result)) {
                $arrAgencias[$row["codigo"]]["CODIGO"] = $row["codigo"];
                $arrAgencias[$row["codigo"]]["NOMBRE"] = $row["nombre"];
            }
        }
        mysqli_close($conn);
        return $arrAgencias;
    }
  }

  public function getTipoUsuario(){
    $conn = getConexion();
    $arrTipoUsuario = array();
    $strQuery = "SELECT id, nombre FROM tipo_usuario WHERE id NOT IN (1)";
    $result = mysqli_query($conn, $strQuery);
    if( !empty($result) ){
      while($row = mysqli_fetch_assoc($result)) {
          $arrTipoUsuario[$row["id"]]["NOMBRE"] = $row["nombre"];
      }
    }
    mysqli_close($conn);
    return $arrTipoUsuario;
    
  }

  public function getUsuarios(){
    $conn = getConexion();
    $arrUsuarios = array();
    $strQuery = "SELECT usuarios.id, 
                        usuarios.nombre, 
                        usuarios.password,
                        usuarios.activo,
                        tipo_usuario.id idtipo,
                        tipo_usuario.nombre nombretipo
                   FROM usuarios
                        INNER JOIN tipo_usuario 
                                ON usuarios.tipo = tipo_usuario.id
                    AND tipo_usuario.id NOT IN(1)
                  ORDER BY usuarios.nombre";
    $result = mysqli_query($conn, $strQuery);
    if( !empty($result) ){
      while($row = mysqli_fetch_assoc($result)) {
        $arrUsuarios[$row["id"]]["NOMBRE"] = $row["nombre"];
        $arrUsuarios[$row["id"]]["PASSWORD"] = $row["password"];
        $arrUsuarios[$row["id"]]["TIPOID"] = $row["idtipo"];
        $arrUsuarios[$row["id"]]["TIPONOMBRE"] = $row["nombretipo"];
        $arrUsuarios[$row["id"]]["AGENCIAS"] = $this->getAgenciasUsuario(intval($row["id"]));
        $arrUsuarios[$row["id"]]["ACTIVO"] = $row["activo"];
        
      }
    }

    mysqli_close($conn);
    return $arrUsuarios;
  }

  public function insertUsuario($strNombre, $strPassword, $intActivo, $intTipoUsuario, $intUser){
    if( $strNombre!='' && $strPassword!='' && $intTipoUsuario>0 && $intUser>0 ){
      $conn = getConexion();
      $strQuery = "INSERT INTO usuarios (nombre, password, tipo, activo, add_fecha, add_user) 
                    VALUES ('{$strNombre}', '{$strPassword}', {$intTipoUsuario}, {$intActivo}, NOW(), {$intUser})";
      mysqli_query($conn, $strQuery);
    }

    $intUltimo = 0;
    $strQuery = "SELECT MAX(id) ultimo FROM usuarios";
    $result = mysqli_query($conn, $strQuery);
    if( !empty($result) ){
      while($row = mysqli_fetch_assoc($result)) {
          $intUltimo = $row["ultimo"];
      }
    }

    return $intUltimo;
  }

  public function deleteUsuario($intUsuario){
      if( $intUsuario>0 ){
          $conn = getConexion();
          $strQuery = "DELETE FROM usuarios WHERE id = {$intUsuario}";
          mysqli_query($conn, $strQuery);

          $strQueryDel = "DELETE FROM usuario_agencias WHERE usuario = {$intUsuario}";
          mysqli_query($conn, $strQueryDel);
      }
  }

  public function updateUsuario($intUsuario, $strNombre, $strPassword, $intActivo, $intTipoUsuario, $intUser){
      if( $intUsuario>0 && $strNombre!='' && $strPassword!='' && $intTipoUsuario>0 && $intUser>0 ){
          $conn = getConexion();
          $strQuery = "UPDATE usuarios 
                          SET nombre = '{$strNombre}', 
                              password = '{$strPassword}', 
                              activo = {$intActivo},
                              tipo = {$intTipoUsuario},
                              mod_fecha = NOW(), 
                              mod_user = {$intUser} 
                        WHERE id = {$intUsuario}";
          mysqli_query($conn, $strQuery);
      }
  }

  public function updateUsuarioAgencia($intUsuario, $arrUsuarioAgencia, $intUser){
    if( $intUsuario>0 && $intUser>0 ){
        $intCountAgencias = count($arrUsuarioAgencia);
        $conn = getConexion();

        $strQueryDel = "DELETE FROM usuario_agencias WHERE usuario = {$intUsuario}";
        mysqli_query($conn, $strQueryDel);
        
        for( $i=0; $i<$intCountAgencias; $i++){
            $strQueryIN = "INSERT INTO usuario_agencias(usuario, agencia, add_user, add_fecha) VALUES({$intUsuario},{$arrUsuarioAgencia[$i]},{$intUser},now())";
            mysqli_query($conn, $strQueryIN);
        }
    }
  }

}

class usuarios_view{

  private $objModel;
  private $arrRolUser;

	public function __construct($arrRolUser){
    $this->objModel = new usuarios_model();
    $this->arrRolUser = $arrRolUser;
  }

  public function drawSelectTipoUsuario($intID, $intSelected){
    if( $intID>0 && $intSelected>0 ){
      ?>
      <select id="selectTipoUsuario_<?php print $intID; ?>" name="selectTipoUsuario_<?php print $intID; ?>" class="form-control">
      <?php
      $arrTipoUsuario = $this->objModel->getTipoUsuario();
      reset($arrTipoUsuario);
      while( $rTMP = each($arrTipoUsuario) ){
        $intID = $rTMP["key"];
        $strNombre = $rTMP["value"]["NOMBRE"];
        $strselected = ($intID == $intSelected)? "selected": "";
        ?>
        <option value="<?php print $intID;?>" <?php print $strselected; ?>><?php print $strNombre;?></option>
        <?php
      }
      ?>
      </select>
      <?php
    }
  }


  public function drawSelectAgencia($intId = 0, $arrUsuarioAgencias = array()){
    $arrAgencias = $this->objModel->getAgencias();
    ?>
    <select id="selectAgencia_<?php print $intId;?>" name="selectAgencia_<?php print $intId;?>[]" style="text-align: center;" class="form-control" multiple>
      <?php
      reset($arrAgencias);
      while( $rTMP = each($arrAgencias) ){
        $intAgencia = $rTMP["key"];
        $strNombreAgencia = $rTMP["value"]["NOMBRE"];
        $strSelected = ( ($rTMP["key"] == $arrUsuarioAgencias[$intAgencia]["CODIGO"]) )? 'selected':'';
        ?>
        <option value="<?php print $intAgencia;?>" <?php print $strSelected;?> ><?php print $strNombreAgencia;?></option>
        <?php    
      }
      ?>
    </select>
    <?php
  }

  public function drawSelectActivo($intId = 0, $intSelected = 0){
    ?>
    <select id="selectActivo_<?php print $intId;?>" name="selectActivo_<?php print $intId;?>" style="text-align: center;" class="form-control">
      <option value="0" <?php print ($intSelected == 0)? 'Selected':'';?> >No</option>
      <option value="1" <?php print ($intSelected == 1)? 'Selected':'';?> >Si</option>
    </select>
    <?php
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
      <!-- AdminLTE Skins. Choose a skin from the css/skins
          folder instead of downloading all of them to reduce the load. -->
      <link rel="stylesheet" href="dist/css/skins/all-md-skins.min.css">
      <!-- Morris chart -->
      <link rel="stylesheet" href="bower_components/morris.js/morris.css">
      <!-- jvectormap -->
      <link rel="stylesheet" href="bower_components/jvectormap/jquery-jvectormap.css">
      <!-- Date Picker -->
      <link rel="stylesheet" href="bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
      <!-- Daterange picker -->
      <link rel="stylesheet" href="bower_components/bootstrap-daterangepicker/daterangepicker.css">
      <!-- bootstrap wysihtml5 - text editor -->
      <link rel="stylesheet" href="plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">

      <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
      <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
      <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
      <![endif]-->

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

        @media only screen and (max-width: 800px) {
                /* Force table to not be like tables anymore */
                #no-more-tables table,
                #no-more-tables thead,
                #no-more-tables tbody,
                #no-more-tables th,
                #no-more-tables td,
                #no-more-tables tr { display: block; }
            
                /* Hide table headers (but not display: none;, for accessibility) */
                #no-more-tables thead tr {
                    position: absolute;
                    top: -9999px;
                    left: -9999px;
                }

                #no-more-tables tr { border: 1px solid #ccc; }
                
                #no-more-tables td {
                    /* Behave like a "row" */
                    border: none;
                    border-bottom: 1px solid #eee;
                    position: relative;
                    padding-left: 50%;
                    white-space: normal;
                    text-align:left;
                }

                #no-more-tables td:before {
                    /* Now like a table header */
                    position: absolute;
                    /* Top/left values mimic padding */
                    top: 6px;
                    left: 6px;
                    width: 45%;
                    padding-right: 10px;
                    white-space: nowrap;
                    text-align:left;
                    font-weight: bold;
                }

                /*
                Label the data
                */
                #no-more-tables td:before { content: attr(data-title); }
            }

      </style>
    </head>
    <body class="hold-transition skin-blue sidebar-mini">
    <div class="wrapper">

      <header class="main-header">
        <!-- Logo -->
        <a href="dashboard.php" class="logo">
          <!-- mini logo for sidebar mini 50x50 pixels -->
          <span class="logo-mini">P<b>A</b></span>
          <!-- logo for regular state and mobile devices -->
          <span class="logo-lg">Premora Agencias</span>
        </a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top">
          <!-- Sidebar toggle button-->
          <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
            <span class="sr-only">Toggle navigation</span>
          </a>
          <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
              <!-- User Account: style can be found in dropdown.less -->
              <li class="dropdown user user-menu">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                  <img src="images/user.png" class="user-image" alt="User Image">
                  <span class="hidden-xs"><?php print $this->arrRolUser["NAME"]; ?></span>
                </a>
                <ul class="dropdown-menu">
                  <!-- User image -->
                  <li class="user-header">
                    <img src="images/user.png" class="img-circle" alt="User Image">
                    <p>
                    <?php print $this->arrRolUser["NAME"]; ?>
                    </p>
                  </li>
                  <!-- Menu Footer-->
                  <li class="user-footer">
                    <div class="pull-center" style="cursor:pointer;">
                      <a href="#" class="btn btn-default btn-flat btn-block" onclick="destroSession()">Cerrar Sesion</a>
                    </div>
                  </li>
                </ul>
              </li>
            </ul>
          </div>
        </nav>
      </header>
      <!-- Left side column. contains the logo and sidebar -->
      <aside class="main-sidebar">
        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar">
          <!-- Sidebar user panel -->
          <div class="user-panel">
            <div class="pull-left image">
              <img src="images/user.png" class="img-circle" alt="User Image">
            </div>
            <div class="pull-left info">
              <p><?php print $this->arrRolUser["NAME"]; ?></p>
              <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
            </div>
          </div>
          <!-- sidebar menu: : style can be found in sidebar.less -->
          <?php draMenu($this->arrRolUser,'cat_usuarios', 2); ?>
        </section>
        <!-- /.sidebar -->
      </aside>

      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <br>
        <section class="content-header">
          <h1>Catálogo de Usuarios</h1>
        </section>
        <!-- Main content -->
        <section class="content">
          <!-- Small boxes (Stat box) -->
          <div class="row">
            <div class="box box-info">
              <div class="box-header">
              </div>
              <div class="box-body">
              <div id="divShowLoadingGeneralBig" style="display:none;" class='centrar'><img src="images/loading.gif" height="300px" width="300px"></div>
              <!-- Detalle -->
              <div id="no-more-tables">
                  <table class="table table-sm table-hover table-condensed" id="tblUsuarios">
                      <thead class="cf">
                          <tr>
                              <th style="text-align:center;">No.</th>
                              <th style="text-align:center;">Nombre</th>
                              <th style="text-align:center;">Password</th>
                              <th style="text-align:center;">Agencia(s)</th>
                              <th style="text-align:center;">Activo</th>
                              <th style="text-align:center;">Tipo Usuario</th>
                              <th colspan="2">&nbsp;</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php
                          $arrUsuarios = $this->objModel->getUsuarios();
                          $intCount = 0;
                          if( count($arrUsuarios)>0 ){
                              reset($arrUsuarios);
                              while( $rTMP = each($arrUsuarios) ){
                                  $intCount++;
                                  $intId = $rTMP["key"];
                                  $strNombre = $rTMP["value"]["NOMBRE"];
                                  $strPassword = $rTMP["value"]["PASSWORD"];
                                  $arrAgencias = $rTMP["value"]["AGENCIAS"];
                                  $intActivo = intval($rTMP["value"]["ACTIVO"]);
                                  $strActivo = ($intActivo == 1)? "Si":"No";
                                  $intTipoUsuario = intval($rTMP["value"]["TIPOID"]);
                                  $strNombreTipoUsuario = $rTMP["value"]["TIPONOMBRE"];
                                  ?>
                                  <tr id="trUsuario_<?php print $intId;?>">
                                      <td data-title="No." style="text-align:center; vertical-align:middle;">
                                          <input id="hdnUsuario_<?php print $intId;?>" name="hdnUsuario_<?php print $intId;?>"  type="hidden" value="N">
                                          <h5><span class="badge badge-success"><?php print $intCount;?></span></h5>
                                      </td>
                                      <td data-title="Nombre" style="text-align:center; vertical-align:middle;">
                                          <div id="divShowUsuarioNombre_<?php print $intId;?>">
                                              <?php print $strNombre;?>
                                          </div>
                                          <div id="divEditUsuarioNombre_<?php print $intId;?>" style="display:none;">
                                              <input class="form-control" type="text" id="txtNombre_<?php print $intId;?>" name="txtNombre_<?php print $intId;?>" value="<?php print $strNombre;?>">
                                          </div>
                                      </td>
                                      <td data-title="Password" style="text-align:center; vertical-align:middle;">
                                          <div id="divShowUsuarioPassword_<?php print $intId;?>">
                                              <?php print $strPassword;?>
                                          </div>
                                          <div id="divEditUsuarioPassword_<?php print $intId;?>" style="display:none;">
                                              <input class="form-control" type="text" id="txtPassword_<?php print $intId;?>" name="txtPassword_<?php print $intId;?>" value="<?php print $strPassword;?>">
                                          </div>
                                      </td>
                                      <td data-title="Agencia" style="text-align:center; vertical-align:middle;">
                                          <div id="divShowUsuarioAgencia_<?php print $intId;?>">
                                          <?php
                                          if( count($arrAgencias)>0 ){
                                              print "<ul>";
                                              reset($arrAgencias);
                                              while( $rTMP1 = each($arrAgencias) ){
                                                  $strUsuarioAgenciaNombre = $rTMP1["value"]["NOMBRE"];
                                                  print "<li>".$strUsuarioAgenciaNombre.'</li></br>';
                                              }
                                              print "</ul>";
                                          }else{
                                              print "Ninguna agencia asignada";
                                          }
                                          ?>
                                          </div>
                                          <div id="divEditUsuarioAgencia_<?php print $intId;?>" style="display:none;">
                                             <?php $this->drawSelectAgencia($intId, $arrAgencias);?>
                                          </div>
                                      </td>
                                      <td data-title="Activo" style="text-align:center; vertical-align:middle;">
                                          <div id="divShowUsuarioActivo_<?php print $intId;?>">
                                              <?php print $strActivo;?>
                                          </div>
                                          <div id="divEditUsuarioActivo_<?php print $intId;?>" style="display:none;">
                                            <?php $this->drawSelectActivo($intId, $intActivo);?>
                                          </div>
                                      </td>
                                      <td data-title="Tipo Usuario" style="text-align:center; vertical-align:middle;">
                                          <div id="divShowUsuarioTipo_<?php print $intId;?>">
                                              <?php print $strNombreTipoUsuario;?>
                                          </div>
                                          <div id="divEditUsuarioTipo_<?php print $intId;?>" style="display:none;">
                                            <?php $this->drawSelectTipoUsuario($intId, $intTipoUsuario);?>
                                          </div>
                                      </td>
                                      <td style="text-align:center; vertical-align:middle;"><button class="btn btn-info btn-raised" onclick="editUsuario('<?php print $intId;?>')" title="Editar">Editar</button></td>
                                  </tr>
                                  <?php
                              }
                          }
                          ?>
                      </tbody>
                  </table>
                  <table class="table table-sm table-hover table-condensed">
                      <tr>
                          <td>
                              <button class="btn btn-success btn-raised btn-block" onclick="agregarUsuario()">(+) Agregar</button>
                          </td>
                          <td>
                              <button class="btn btn-success btn-raised btn-block" onclick="checkForm()">Guardar</button>
                          </td>
                      </tr>
                  </table>
              </div>
              <!-- Detalle --> 
              </div>
            </div>
          </div>
        </section>
        <!-- /.content -->
      </div>
      <!-- /.content-wrapper -->
      <footer class="main-footer">
        <div class="pull-right hidden-xs">
          <b>Version</b> 1.0
        </div>
        <strong>Copyright &copy; 2020
      </footer>
      <!-- Add the sidebar's background. This div must be placed
          immediately after the control sidebar -->
      <div class="control-sidebar-bg"></div>
    </div>
    <!-- ./wrapper -->

    <!-- jQuery 3 -->
    <script src="bower_components/jquery/dist/jquery.min.js"></script>
    <!-- jQuery UI 1.11.4 -->
    <script src="bower_components/jquery-ui/jquery-ui.min.js"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
      $.widget.bridge('uibutton', $.ui.button);
    </script>
    <!-- Bootstrap 3.3.7 -->
    <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- Material Design -->
    <script src="dist/js/material.min.js"></script>
    <script src="dist/js/ripples.min.js"></script>
    <script>
        $.material.init();

        function destroSession(){
            if (confirm("¿Desea salir de la aplicación?")) {
                $.ajax({
                    url:"cat_usuarios.php",
                    data:
                    {
                        destroSession:true
                    },
                    type:"post",
                    dataType: "json",
                    success:function(data){
                        if ( data.Correcto == "Y" ){
                        alert("Usted ha cerrado sesión");
                        location.href = "index.php";
                        }
                    }
                });
            }
        }

        function deleteUsuario(id){
          $("#trUsuario_"+id).css('background-color','#f4d0de');
          $("#hdnUsuario_"+id).val("D");
        }

        function editUsuario(id){
          $("#divEditUsuarioNombre_"+id).show();
          $("#divShowUsuarioNombre_"+id).hide();

          $("#divEditUsuarioPassword_"+id).show();
          $("#divShowUsuarioPassword_"+id).hide();

          $("#divEditUsuarioAgencia_"+id).show();
          $("#divShowUsuarioAgencia_"+id).hide();

          $("#divEditUsuarioActivo_"+id).show();
          $("#divShowUsuarioActivo_"+id).hide();

          $("#divEditUsuarioTipo_"+id).show();
          $("#divShowUsuarioTipo_"+id).hide();

          $("#hdnUsuario_"+id).val("E");
        }

        function fntGetCountUsuarios(){
            var intCount = 0;
            $("input[name*='txtNombre_']").each(function(){
                intCount++;   
            });
            return intCount;  
        }

        var intFilasUsuario = 0;
        function agregarUsuario(){
            intFilasUsuario = fntGetCountUsuarios();
            intFilasUsuario++;

            var $tabla = $("#tblUsuarios");
            var $tr = $("<tr></tr>");
            // creamos la columna o td
            var $td = $("<td data-title='No.' style='text-align:center;'><b>"+intFilasUsuario+"<b><input class='form-control' type='hidden' id='hdnUsuario_"+intFilasUsuario+"' name='hdnUsuario_"+intFilasUsuario+"' value='A'></td>")
            $tr.append($td);

            var $td = $("<td data-title='Nombre' style='text-align:center;'><input class='form-control' type='text' id='txtNombre_"+intFilasUsuario+"' name='txtNombre_"+intFilasUsuario+"'></td>")
            $tr.append($td);

            var $td = $("<td data-title='Password' style='text-align:center;'><input class='form-control' type='text' id='txtPassword_"+intFilasUsuario+"' name='txtPassword_"+intFilasUsuario+"'></td>")
            $tr.append($td);

            var $td = $("<td data-title='Agencia(s)' style='text-align:center;'><select class='form-control' id='selectAgencia_"+intFilasUsuario+"' name='selectAgencia_"+intFilasUsuario+"[]' style='text-align: center;' multiple><?php $arrAgencias = $this->objModel->getAgencias();reset($arrAgencias);while( $rTMP = each($arrAgencias) ){?><option value='<?php print $rTMP["key"];?>'><?php print $rTMP["value"]["NOMBRE"];?></option><?php } ?></select></td>");
            $tr.append($td);

            var $td = $("<td data-title='Activo' style='text-align:center;'><select class='form-control' id='selectActivo_"+intFilasUsuario+"' name='selectActivo_"+intFilasUsuario+"' style='text-align: center;'><option value='0'>No</option><option value='1'>Si</option></select></td>");
            $tr.append($td);

            var $td = $("<td data-title='Tipo Usuario' style='text-align:center;'><select class='form-control' id='selectTipoUsuario_"+intFilasUsuario+"' name='selectTipoUsuario_"+intFilasUsuario+"' style='text-align: center;'><?php $arrTipoUsuario = $this->objModel->getTipoUsuario();reset($arrTipoUsuario);while( $rTMP = each($arrTipoUsuario) ){?><option value='<?php print $rTMP["key"];?>'><?php print utf8_encode($rTMP["value"]["NOMBRE"]);?></option><?php } ?></select></td>");
            $tr.append($td);

            var $td = $("<td style='text-align:center; display:none;'></td>");
            $tr.append($td);
            var $td = $("<td style='text-align:center; display:none;'></td>");
            $tr.append($td);

            $tabla.append($tr); 
        }

        function checkForm(){
          var boolError = false;

          $("input[name*='txtNombre_']").each(function(){
              if( $(this).val() == '' ){
                  $(this).css('background-color','#f4d0de');
                  boolError = true;
              }else{
                  $(this).css('background-color','');
              }   
          });

          $("input[name*='txtPassword_']").each(function(){
              if( $(this).val() == '' ){
                  $(this).css('background-color','#f4d0de');
                  boolError = true;
              }else{
                  $(this).css('background-color','');
              }   
          });

          if( boolError == false ){
              var objSerialized = $("#tblUsuarios").find("select, input").serialize();
              $.ajax({
                  url:"cat_usuarios.php",
                  data: objSerialized,
                  type:"POST",
                  beforeSend: function() {
                      $("#divShowLoadingGeneralBig").show();
                  },
                  success:function(data){
                      $("#divShowLoadingGeneralBig").hide();
                      location.href = "cat_usuarios.php"; 
                  }
              });
          }else{
              alert('Faltan campos por llenar en el formulario');
          }
      }

    </script>
    <!-- Morris.js charts -->
    <script src="bower_components/raphael/raphael.min.js"></script>
    <script src="bower_components/morris.js/morris.min.js"></script>
    <!-- Sparkline -->
    <script src="bower_components/jquery-sparkline/dist/jquery.sparkline.min.js"></script>
    <!-- jvectormap -->
    <script src="plugins/jvectormap/jquery-jvectormap-1.2.2.min.js"></script>
    <script src="plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
    <!-- jQuery Knob Chart -->
    <script src="bower_components/jquery-knob/dist/jquery.knob.min.js"></script>
    <!-- daterangepicker -->
    <script src="bower_components/moment/min/moment.min.js"></script>
    <script src="bower_components/bootstrap-daterangepicker/daterangepicker.js"></script>
    <!-- datepicker -->
    <script src="bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <!-- Bootstrap WYSIHTML5 -->
    <script src="plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>
    <!-- Slimscroll -->
    <script src="bower_components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
    <!-- FastClick -->
    <script src="bower_components/fastclick/lib/fastclick.js"></script>
    <!-- AdminLTE App -->
    <script src="dist/js/adminlte.min.js"></script>
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    <script src="dist/js/pages/dashboard.js"></script>
    <!-- AdminLTE for demo purposes -->
    <script src="dist/js/demo.js"></script>
    </body>
    </html>
    <?php
  }

}

?>



