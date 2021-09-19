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

$objController = new tg_cobro_controller($arrRolUser);
$objController->process();
$objController->runAjax();
$objController->drawContentController();

class tg_cobro_controller{

  private $objModel;
  private $objView;
  private $arrRolUser;
  
  public function __construct($arrRolUser){
    $this->objModel = new tg_cobro_model();
    $this->objView = new tg_cobro_view($arrRolUser);
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
        if( $arrExplode[0] == "hdnTipoGestion"){
            $intTipoGestion = $arrExplode[1];
            $strAccion = isset($_POST["hdnTipoGestion_{$intTipoGestion}"]) ? trim($_POST["hdnTipoGestion_{$intTipoGestion}"]):'';
            $intCategoria = isset($_POST["selectCategoria_{$intTipoGestion}"]) ? intval($_POST["selectCategoria_{$intTipoGestion}"]):'';
            $strNombre = isset($_POST["txtTGNombre_{$intTipoGestion}"]) ? trim($_POST["txtTGNombre_{$intTipoGestion}"]):'';
            
            if( $strAccion == "A" ){
                $this->objModel->insertTipoGestion($intCategoria, $strNombre, $this->arrRolUser["ID"]);
            }elseif( $strAccion == "D" ){
                $this->objModel->deleteTipoGestion($intTipoGestion);
            }elseif( $strAccion == "E" ){
                $this->objModel->updateTipoGestion($intTipoGestion, $intCategoria, $strNombre, $this->arrRolUser["ID"]);
            }
        }
    }
}

}

class tg_cobro_model{

  function getSubCategoriagestion(){
    $conn = getConexion();
    $arrCategorias = array();
    $strQuery = "SELECT subcategorias_gestiones.id, 
                        subcategorias_gestiones.nombre,
                        categorias_gestiones.id idcategoria,
                        categorias_gestiones.nombre categoria
                   FROM subcategorias_gestiones
                        INNER JOIN categorias_gestiones 
                                ON subcategorias_gestiones.categoria_gestion = categorias_gestiones.id 
                  ORDER BY subcategorias_gestiones.nombre";
    $result = mysqli_query($conn, $strQuery);
    if( !empty($result) ){
      while($row = mysqli_fetch_assoc($result)) {
        $arrCategorias[$row["id"]]["ID_CATEGORIA"] = $row["idcategoria"];
        $arrCategorias[$row["id"]]["CATEGORIA"] = $row["categoria"];
        $arrCategorias[$row["id"]]["NOMBRE"] = $row["nombre"];
      }
    }

    mysqli_close($conn);
    return $arrCategorias;
  }

  function getCategoriagestion(){
    $conn = getConexion();
    $arrCategoria = array();
    $strQuery = "SELECT id,
                        nombre
                   FROM categorias_gestiones
                  ORDER BY nombre";
    $result = mysqli_query($conn, $strQuery);
    if( !empty($result) ){
      while($row = mysqli_fetch_assoc($result)) {
        $arrCategoria[$row["id"]]["NOMBRE"] = $row["nombre"];
      }
    }

    mysqli_close($conn);
    return $arrCategoria;
  }

  public function insertTipoGestion($intCategoria, $strNombre, $intUser){
    if( $intCategoria>0 && $strNombre!='' && $intUser>0 ){
        $conn = getConexion();
        $strQuery = "INSERT INTO subcategorias_gestiones (categoria_gestion, nombre, add_fecha, add_user) VALUES ({$intCategoria}, '{$strNombre}', NOW(), {$intUser})";
        mysqli_query($conn, $strQuery);
    }
  }

  public function deleteTipoGestion($intTipoGestion){
      if( $intTipoGestion>0 ){
          $conn = getConexion();
          $strQuery = "DELETE FROM subcategorias_gestiones WHERE id = {$intTipoGestion}";
          mysqli_query($conn, $strQuery);
      }
  }

  public function updateTipoGestion($intTipoGestion, $intCategoria, $strNombre, $intUser){
      if( $intTipoGestion>0 && $intCategoria>0 &&  $strNombre!='' && $intUser>0 ){
          $conn = getConexion();
          $strQuery = "UPDATE subcategorias_gestiones 
                          SET categoria_gestion = {$intCategoria}, 
                              nombre = '{$strNombre}', 
                              mod_fecha = NOW(), 
                              mod_user = {$intUser} 
                        WHERE id = {$intTipoGestion}";
          mysqli_query($conn, $strQuery);
      }
  }
}

class tg_cobro_view{

  private $objModel;
  private $arrRolUser;

	public function __construct($arrRolUser){
    $this->objModel = new tg_cobro_model();
    $this->arrRolUser = $arrRolUser;
  }

  public function drawSelectCategoria($intID, $intSelected){
    if( $intID>0 && $intSelected>0 ){
      ?>
      <select id="selectCategoria_<?php print $intID; ?>" name="selectCategoria_<?php print $intID; ?>" class="form-control">
      <?php
      $arrCategoria = $this->objModel->getCategoriagestion();
      reset($arrCategoria);
      while( $rTMP = each($arrCategoria) ){
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
          <?php draMenu($this->arrRolUser,'cat_tipo_gestion_cobro', 2); ?>
        </section>
        <!-- /.sidebar -->
      </aside>

      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <br>
        <section class="content-header">
          <h1>Catálogo de Tipo Gestión de Cobro</h1>
        </section>
        <!-- Main content -->
        <section class="content">
          <!-- Small boxes (Stat box) -->
          <div class="row">
            <div class="box box-info">
              <div class="box-header">
              </div>
              <div class="box-body">
              <!-- Detalle -->
              <div id="no-more-tables">
                  <table class="table table-sm table-hover table-condensed" id="tblTipoGestionCobro">
                      <thead class="cf">
                          <tr>
                              <th style="text-align:center;">No.</th>
                              <th style="text-align:center;">Categoria</th>
                              <th style="text-align:center;">Nombre Tipo Gestión</th>
                              <th style="text-align:center;" colspan="2">Acciones</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php
                          $arrCategorias = $this->objModel->getSubCategoriagestion();
                          $intCount = 0;
                          if( count($arrCategorias)>0 ){
                              reset($arrCategorias);
                              while( $rTMP = each($arrCategorias) ){
                                  $intCount++;
                                  $intId = $rTMP["key"];
                                  $intIdCategoria = $rTMP["value"]["ID_CATEGORIA"];
                                  $strNombreCategoria = utf8_encode($rTMP["value"]["CATEGORIA"]);
                                  $strNombreTipo = utf8_encode($rTMP["value"]["NOMBRE"]);
                                  ?>
                                  <tr id="trTipoGestion_<?php print $intId;?>">
                                      <td data-title="No." style="text-align:center; vertical-align:middle;">
                                          <input id="hdnTipoGestion_<?php print $intId;?>" name="hdnTipoGestion_<?php print $intId;?>"  type="hidden" value="N">
                                          <h5><span class="badge badge-success"><?php print $intCount;?></span></h5>
                                      </td>
                                      <td data-title="Categoria" style="text-align:center; vertical-align:middle;">
                                          <div id="divShowTGCategoria_<?php print $intId;?>">
                                              <?php print $strNombreCategoria;?>
                                          </div>
                                          <div id="divEditTGCategoria_<?php print $intId;?>" style="display:none;">
                                              <?php $this->drawSelectCategoria($intCount, $intIdCategoria);?>
                                          </div>
                                      </td>
                                      <td data-title="Nombre Tipo Gestión" style="text-align:center; vertical-align:middle;">
                                          <div id="divShowTGNombre_<?php print $intId;?>">
                                              <?php print $strNombreTipo;?>
                                          </div>
                                          <div id="divEditTGNombre_<?php print $intId;?>" style="display:none;">
                                              <input class="form-control" type="text" id="txtTGNombre_<?php print $intId;?>" name="txtTGNombre_<?php print $intId;?>" value="<?php print $strNombreTipo;?>">
                                          </div>
                                      </td>
                                      <td data-title="Acciones" style="text-align:center;">
                                        <button class="btn btn-info btn-raised btn-block" onclick="editTGC('<?php print $intId;?>')" title="Editar">
                                          <i class="fa fa-pencil" aria-hidden="true"></i> Editar
                                        </button>
                                        <button class="btn btn-danger btn-raised btn-block" onclick="deleteTCG('<?php print $intId;?>')" title="Eliminar">
                                          <i class="fa fa-trash" aria-hidden="true"></i> Eliminar
                                        </button>
                                      </td>
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
                              <button class="btn btn-success btn-raised btn-block" onclick="agregarTGC()">
                                <i class="fa fa-plus" aria-hidden="true"></i> Agregar
                              </button>
                          </td>
                          <td>
                              <button class="btn btn-success btn-raised btn-block" onclick="checkForm()">
                                <i class="fa fa-floppy-o" aria-hidden="true"></i> Guardar
                              </button>
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
                    url:"cat_agencias.php",
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

        function deleteTCG(id){
          $("#trTipoGestion_"+id).css('background-color','#f4d0de');
          $("#hdnTipoGestion_"+id).val("D");
        }

        function editTGC(id){
          $("#divEditTGCategoria_"+id).show();
          $("#divShowTGCategoria_"+id).hide();

          $("#divEditTGNombre_"+id).show();
          $("#divShowTGNombre_"+id).hide();

          $("#hdnTipoGestion_"+id).val("E");
        }

        function fntGetCountTGC(){
            var intCount = 0;
            $("input[name*='txtTGNombre_']").each(function(){
                intCount++;   
            });
            return intCount;  
        }

        var intFilasTGC = 0;
        function agregarTGC(){
            intFilasTGC = fntGetCountTGC();
            intFilasTGC++;

            var $tabla = $("#tblTipoGestionCobro");
            var $tr = $("<tr></tr>");
            // creamos la columna o td
            var $td = $("<td data-title='No.' style='text-align:center;'><b>"+intFilasTGC+"<b><input class='form-control' type='hidden' id='hdnTipoGestion_"+intFilasTGC+"' name='hdnTipoGestion_"+intFilasTGC+"' value='A'></td>")
            $tr.append($td);

            var $td = $("<td data-title='Categoria' style='text-align:center;'><select class='form-control' id='selectCategoria_"+intFilasTGC+"' name='selectCategoria_"+intFilasTGC+"' style='text-align: center;'><?php $arrCategoria = $this->objModel->getCategoriagestion();reset($arrCategoria);while( $rTMP = each($arrCategoria) ){?><option value='<?php print $rTMP["key"];?>'><?php print utf8_encode($rTMP["value"]["NOMBRE"]);?></option><?php } ?></select></td>");
            $tr.append($td);

            var $td = $("<td data-title='Nombre Tipo Gestión' style='text-align:center;'><input class='form-control' type='text' id='txtTGNombre_"+intFilasTGC+"' name='txtTGNombre_"+intFilasTGC+"'></td>");
            $tr.append($td);

            var $td = $("<td style='text-align:center; display:none;'></td>");
            $tr.append($td);
            var $td = $("<td style='text-align:center; display:none;'></td>");
            $tr.append($td);

            $tabla.append($tr); 
        }

        function checkForm(){
          var boolError = false;

          $("input[name*='txtTGNombre_']").each(function(){
              if( $(this).val() == '' ){
                  $(this).css('background-color','#f4d0de');
                  boolError = true;
              }else{
                  $(this).css('background-color','');
              }   
          });

          if( boolError == false ){
              var objSerialized = $("#tblTipoGestionCobro").find("select, input").serialize();
              $.ajax({
                  url:"cat_tipo_gestion_cobro.php",
                  data: objSerialized,
                  type:"POST",
                  beforeSend: function() {
                      $("#divShowLoadingGeneralBig").show();
                  },
                  success:function(data){
                      $("#divShowLoadingGeneralBig").hide();
                      location.href = "cat_tipo_gestion_cobro.php"; 
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



