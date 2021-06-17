<?php
require_once("core/core.php");
error_reporting(E_ALL);
ini_set('display_errors',0);
session_start();
if ( isset($_SESSION['user_id']) ) {
  $strRolUserSession = getRolUserSession($_SESSION['user_id']);
  $intIDUserSession = getIDUserSession($_SESSION['user_id']);
  $intAgenciaUserSession = getAgenciaByUsuario($intIDUserSession);

  if( $strRolUserSession != '' ){
    $arrRolUser["ID"] = $intIDUserSession;
    $arrRolUser["NAME"] = $_SESSION['user_id'];
    $arrRolUser["AGENCIA"] = $intAgenciaUserSession;

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

$objController = new rpt_gestiones_controller($arrRolUser);
$objController->runAjax();
$objController->drawContentController();

class rpt_gestiones_controller{

  private $objModel;
  private $objView;
  private $arrRolUser;
  
  public function __construct($arrRolUser){
    $this->objModel = new rpt_gestiones_model($arrRolUser);
    $this->objView = new rpt_gestiones_view($arrRolUser);
    $this->arrRolUser = $arrRolUser;
  }

  public function drawContentController(){
    $this->objView->drawContent(); 
  }

  public function runAjax(){
    $this->exportPDF();
    $this->ajaxDestroySession();
    $this->ajaxgetDetailReporte();
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

  public function ajaxgetDetailReporte(){
    if( isset($_POST["txtFechaInicio"]) && $_POST["txtFechaInicio"] != ''){
      $intUsuario = isset($_POST["selectUsuarios"])? intval($_POST["selectUsuarios"]): 0;
      $intTipoRiesgo = isset($_POST["selectTipoRiesgo"])? intval($_POST["selectTipoRiesgo"]): 0;
      $intSubcategoria = isset($_POST["sltClasificacion"])? intval($_POST["sltClasificacion"]): 0;
      $strFechaInicio = isset($_POST["txtFechaInicio"])? trim($_POST["txtFechaInicio"]): "";
      $strFechaFinal = isset($_POST["txtFechaFinal"])? trim($_POST["txtFechaFinal"]): "";
      $strMostrarDetalle = isset($_POST["sltMostrarDetalle"])? $_POST["sltMostrarDetalle"]: "";
      $boolDetalle = ($strMostrarDetalle == "Y")? true:false;
          
      $arrDetail = $this->objModel->getGestionesPago($intUsuario, $intTipoRiesgo, $intSubcategoria, $strFechaInicio, $strFechaFinal);
      $arrConteo = $this->objModel->getConteoClasificacion($intUsuario, $intTipoRiesgo, $intSubcategoria, $strFechaInicio, $strFechaFinal);
      if( count($arrDetail)>0 ){
        ?>
        <script>
          $("#btnExportarExcel").show();
        </script>
        <?php
      }else{
        ?>
        <script>
          $("#btnExportarExcel").hide();
        </script>
        <?php
      }

      print $this->objView->drawContentDetail($arrDetail, $arrConteo, false, $boolDetalle);
      exit();
    }
  }

  public function exportPDF(){
    if( isset($_POST["hdnExportar"]) && $_POST["hdnExportar"] == 'true' ){
        require_once("tcpdf/tcpdf.php");
        $strTipoExportar = isset($_POST["TipoExport"])? $_POST["TipoExport"] :'';
        $strNombreArchivo = "ReporteGestionesdePago_".date("d")."/".date("m")."/".date("Y");
        
        //Validacion tipo de formato exportado
        if($strTipoExportar == 'EXCEL'){
          $intUsuario = isset($_POST["intUsuarios"])? intval($_POST["intUsuarios"]): 0;
          $intTipoRiesgo = isset($_POST["intTipoRiesgo"])? intval($_POST["intTipoRiesgo"]): 0;
          $intSubcategoria = isset($_POST["sltClasificacion"])? intval($_POST["sltClasificacion"]): 0;
          $strFechaInicio = isset($_POST["strFechaInicial"])? trim($_POST["strFechaInicial"]): "";
          $strFechaFinal = isset($_POST["strFechaFinal"])? trim($_POST["strFechaFinal"]): "";
          $strMostrarDetalle = isset($_POST["sltMostrarDetalle"])? $_POST["sltMostrarDetalle"]: "";
          $boolDetalle = ($strMostrarDetalle == "Y")? true:false;
              
          $arrDetail = $this->objModel->getGestionesPago($intUsuario, $intTipoRiesgo, $intSubcategoria, $strFechaInicio, $strFechaFinal);
          $arrConteo = $this->objModel->getConteoClasificacion($intUsuario, $intTipoRiesgo, $intSubcategoria, $strFechaInicio, $strFechaFinal);

          header("Pragma: no-cache");
          header('Cache-control: ');
          header("Expires: Mon, 26 Jul 2027 05:00:00 GMT");
          header("Last-Modified: " .gmdate("D, d M Y H:i:s"). " GMT");
          header("Cache-Control: no-store, no-cache, must-revalidate");
          header("Cache-Control: post-check=0, pre-check=0", false);
          header("Content-type: application/ms-excel");
          header("Content-disposition: attachment; filename={$strNombreArchivo}.xls"); 
          print utf8_encode($this->objView->drawContentDetail($arrDetail, $arrConteo, true, $boolDetalle));
          exit(); 
        }
    }
}

}

class rpt_gestiones_model{

  private $arrRolUser;

	public function __construct($arrRolUser){
    $this->arrRolUser = $arrRolUser;
  }

  public function getUsuarios(){
    $conn = getConexion();
    $arrUsuarios = array();
    $strQuery = "SELECT id, nombre FROM usuarios WHERE tipo = 2 AND activo = 1";
    $result = mysqli_query($conn, $strQuery);
    if( !empty($result) ){  
      while($row = mysqli_fetch_assoc($result)) {
        $arrUsuarios[$row["id"]]["NOMBRE"] = $row["nombre"];
      }
    }

    mysqli_close($conn);
    return $arrUsuarios;
  }

  public function getTipoRiesgo(){
    $conn = getConexion();
    $arrTR = array();
    $strQuery = "SELECT id, nombre FROM estado_premora ORDER BY nombre";
    $result = mysqli_query($conn, $strQuery);
    if( !empty($result) ){  
      while($row = mysqli_fetch_assoc($result)) {
        $arrTR[$row["id"]]["NOMBRE"] = $row["nombre"];
      }
    }

    mysqli_close($conn);
    return $arrTR;
  }

  public function getEstadoActual($intPrestamo){
    if( $intPrestamo >0 ){
      $conn = getConexion();
      $strEstado = "";
      $strQuery  = "SELECT 
                    CASE
                        WHEN dias_mora_capital = 0 THEN 'Al dia'
                        WHEN dias_mora_capital >0 AND dias_mora_capital <=30 THEN 'Premora'
                        WHEN dias_mora_capital >30  THEN 'En mora'
                        ELSE 'Cancelado'
                    END AS estado_actual
                    FROM listado_condiciones
                    WHERE numero_prestamo = {$intPrestamo}";
      $result = mysqli_query($conn, $strQuery);
      if( !empty($result) ){
        while($row = mysqli_fetch_assoc($result)) {
          $strEstado = $row["estado_actual"];
        }
      }

      if( $strEstado == '' ){
        $strEstado = "Cancelado";
      }

      mysqli_close($conn);
      return $strEstado;
    }
  }

  public function getSaldoListado($intPrestamo){
    if( $intPrestamo >0 ){
      $conn = getConexion();
      $strSaldo = "";
      $strQuery = "SELECT saldo_capital
                     FROM listado_condiciones
                    WHERE numero_prestamo = {$intPrestamo}";
      $result = mysqli_query($conn, $strQuery);
      if( !empty($result) ){
        while($row = mysqli_fetch_assoc($result)) {
          $strSaldo = $row["saldo_capital"];
        }
      }

      if( $strSaldo == '' ){
        $strSaldo = 0;
      }

      mysqli_close($conn);
      return $strSaldo;
    }
  }

  public function getRangosTipoRiesgo(){
    $conn = getConexion();
    $arrRangosTipoRiesgo = array();
    $strQuery = "SELECT id, rango_inicial, rango_final FROM estado_premora";
    $result = mysqli_query($conn, $strQuery);
    if( !empty($result) ){  
      while($row = mysqli_fetch_assoc($result)) {
        $arrRangosTipoRiesgo[$row["id"]]["RANGO_INICIAL"] = $row["rango_inicial"];
        $arrRangosTipoRiesgo[$row["id"]]["RANGO_FINAL"] = $row["rango_final"];
      }
    }

    mysqli_close($conn);
    return $arrRangosTipoRiesgo;
  }

  public function getConteoClasificacion($intUsuario, $intTipoRiesgo, $intSubcategoria, $strFechaInicio, $strFechaFinal){
    if( $strFechaInicio!='' && $strFechaFinal!=''){

      $arrRangosTipoRiesgo = $this->getRangosTipoRiesgo();

      $arrConteoClasificacion = array();
      $strAndUsuario = ($intUsuario != 0)? "AND promesa_pago.add_user = {$intUsuario}": "";
      $strAndTipoRiesgo = "";
      if( $intTipoRiesgo == 1 ){
        $strAndTipoRiesgo = "AND prestamo.saldo_capital BETWEEN {$arrRangosTipoRiesgo[1]["RANGO_INICIAL"]} AND {$arrRangosTipoRiesgo[1]["RANGO_FINAL"]} ";
      }

      if( $intTipoRiesgo == 2 ){
        $strAndTipoRiesgo = "AND prestamo.saldo_capital BETWEEN {$arrRangosTipoRiesgo[2]["RANGO_INICIAL"]} AND {$arrRangosTipoRiesgo[2]["RANGO_FINAL"]} ";
      }

      if( $intTipoRiesgo == 3 ){
        $strAndTipoRiesgo = "AND prestamo.saldo_capital BETWEEN {$arrRangosTipoRiesgo[3]["RANGO_INICIAL"]} AND {$arrRangosTipoRiesgo[3]["RANGO_FINAL"]} ";
      }

      $strAndSubcategoria = ($intSubcategoria>0)? "AND subcategorias_gestiones.id = {$intSubcategoria}":"";

      $strAndFecha = "AND CAST(promesa_pago.add_fecha AS DATE) BETWEEN '{$strFechaInicio}' AND '{$strFechaFinal}'";
      $conn = getConexion();
      $strQuery = "SELECT categorias_gestiones.nombre categoria, 
                          subcategorias_gestiones.nombre subcategoria, 
                          COUNT(promesa_pago.id) conteo 
                     FROM promesa_pago 
                          INNER JOIN subcategorias_gestiones 
                                  ON promesa_pago.subcategoria_gestion = subcategorias_gestiones.id
                          INNER JOIN categorias_gestiones 
                                  ON subcategorias_gestiones.categoria_gestion = categorias_gestiones.id
                          INNER JOIN prestamo 
                                  ON promesa_pago.prestamo = prestamo.numero
                    WHERE promesa_pago.id IS NOT NULL
                          {$strAndFecha}
                          {$strAndUsuario}
                          {$strAndTipoRiesgo}
                          {$strAndSubcategoria}
                    GROUP BY subcategorias_gestiones.nombre
                    ORDER BY conteo DESC";
      $result = mysqli_query($conn, $strQuery);
      if( !empty($result) ){  
        while($row = mysqli_fetch_assoc($result)) {
          $arrConteoClasificacion[$row["categoria"]]["SUBCATEGORIA"][$row["subcategoria"]]["CONTEO"] = $row["conteo"];
        }
      }
  
      mysqli_close($conn);
      return $arrConteoClasificacion;

    }

  }

  public function getGestionesPago($intUsuario, $intTipoRiesgo, $intSubcategoria, $strFechaInicio, $strFechaFinal){
    //
    if( $strFechaInicio!='' && $strFechaFinal!=''){
      //
      $arrGestiones = array();
      $arrRangosTipoRiesgo = $this->getRangosTipoRiesgo();
      $strAndUsuario = ($intUsuario != 0)? "AND promesa_pago.add_user = {$intUsuario}": "";
      $strAndTipoRiesgo = "";
      if( $intTipoRiesgo == 1 ){
        $strAndTipoRiesgo = "AND prestamo.saldo_capital BETWEEN {$arrRangosTipoRiesgo[1]["RANGO_INICIAL"]} AND {$arrRangosTipoRiesgo[1]["RANGO_FINAL"]} ";
      }

      if( $intTipoRiesgo == 2 ){
        $strAndTipoRiesgo = "AND prestamo.saldo_capital BETWEEN {$arrRangosTipoRiesgo[2]["RANGO_INICIAL"]} AND {$arrRangosTipoRiesgo[2]["RANGO_FINAL"]} ";
      }

      if( $intTipoRiesgo == 3 ){
        $strAndTipoRiesgo = "AND prestamo.saldo_capital BETWEEN {$arrRangosTipoRiesgo[3]["RANGO_INICIAL"]} AND {$arrRangosTipoRiesgo[3]["RANGO_FINAL"]} ";
      }

      $strAndSubcategoria = ($intSubcategoria>0)? "AND subcategorias_gestiones.id = {$intSubcategoria}":"";

      $strAndFecha = "AND CAST(promesa_pago.add_fecha AS DATE) BETWEEN '{$strFechaInicio}' AND '{$strFechaFinal}'";

      $conn = getConexion();
      $strQuery = "SELECT promesa_pago.id,
                          usuarios.nombre usuario_nombre, 
                          promesa_pago.prestamo, 
                          promesa_pago.descripcion, 
                          DATE_FORMAT(promesa_pago.add_fecha, '%d/%m/%Y') fecha,
                          prestamo.saldo_capital,
                          subcategorias_gestiones.nombre nombrecategoria,
                          prestamo.estado_actual 
                     FROM promesa_pago
                          LEFT JOIN prestamo 
                                 ON promesa_pago.prestamo = prestamo.numero
                          INNER JOIN usuarios 
                                  ON promesa_pago.add_user = usuarios.id
                          LEFT JOIN subcategorias_gestiones
                                 ON promesa_pago.subcategoria_gestion = subcategorias_gestiones.id
                    WHERE promesa_pago.id IS NOT NULL
                          {$strAndFecha}
                          {$strAndUsuario}
                          {$strAndTipoRiesgo}
                          {$strAndSubcategoria}
                    ORDER BY promesa_pago.add_fecha, prestamo.saldo_capital";
      $result = mysqli_query($conn, $strQuery);
      if( !empty($result) ){  
        while($row = mysqli_fetch_assoc($result)) {
          $arrGestiones[$row["id"]]["USUARIO"] = $row["usuario_nombre"];
          $arrGestiones[$row["id"]]["PRESTAMO"] = $row["prestamo"];
          $arrGestiones[$row["id"]]["DESCRIPCION"] = $row["descripcion"];
          $arrGestiones[$row["id"]]["SALDO_CAPITAL"] = $row["saldo_capital"];
          $arrGestiones[$row["id"]]["CLASIFICACION"] = $row["nombrecategoria"];
          $arrGestiones[$row["id"]]["ESTADO_ACTUAL"] = $row["estado_actual"];
          $arrGestiones[$row["id"]]["FECHA"] = $row["fecha"];
        }
      }
  
      mysqli_close($conn);
      return $arrGestiones;
    }
  }

}

class rpt_gestiones_view{

  private $objModel;
  private $arrRolUser;

	public function __construct($arrRolUser){
    $this->objModel = new rpt_gestiones_model($arrRolUser);
    $this->arrRolUser = $arrRolUser;
  }

  public function drawSelectUsuarios(){
    $arrUsuarios = $this->objModel->getUsuarios();
    ?>
    <select id="selectUsuarios" name="selectUsuarios" style="text-align: center;" class="form-control">
        <option value="0">- Todos las usuarios -</option>
        <?php
        reset($arrUsuarios);
        while( $rTMP = each($arrUsuarios) ){ 
          $intID =  $rTMP["key"];
          $strNombre = utf8_encode($rTMP["value"]["NOMBRE"]);
          ?>
          <option value="<?php print $intID;?>"><?php print $strNombre;?></option>
          <?php   
        }
        ?>
    </select>
    <?php
  }

  public function drawSelectTipoRiesgo(){
    $arrTR = $this->objModel->getTipoRiesgo();
    ?>
    <select id="selectTipoRiesgo" name="selectTipoRiesgo" style="text-align: center;" class="form-control">
        <option value="0">- Todos los tipos de riesgo -</option>
        <?php
        reset($arrTR);
        while( $rTMP = each($arrTR) ){ 
          $intID =  $rTMP["key"];
          $strNombre = utf8_encode($rTMP["value"]["NOMBRE"]);
          ?>
          <option value="<?php print $intID;?>"><?php print $strNombre;?></option>
          <?php   
        }
        ?>
    </select>
    <?php
  }

  public function drawSelectClasificacion(){
    $arrSubCategorias = getSubcategoriasGestiones();
    ?>
    <select id="sltClasificacion" name="sltClasificacion" class="form-control" style="text-align: center;">
        <option value="0">-- Todas las Clasificaciones --</option>
        <?php
        reset($arrSubCategorias);
        while( $rTMP = each($arrSubCategorias) ){
            $strCatGestion = $rTMP["key"];
            ?>
            <optgroup label="<?php print $strCatGestion;?>">
            <?php
            reset($rTMP["value"]["DETAIL"]);
            while( $rTMP2 = each($rTMP["value"]["DETAIL"]) ){
                $intID = $rTMP2["key"];
                $strLabel = trim(utf8_encode($rTMP2["value"]["NOMBRE"]));
                ?>
                <option value="<?php print $intID;?>"><?php print $strLabel;?></option>
                <?php
            }
            ?>
            </optgroup>
            <?php
        }
        ?>
    </select>
    <?php
  }

  public function drawContentDetail($arrDetail, $arrConteo, $boolExport = false, $boolDetalle = true ){
    if( count($arrDetail)>0 ){
      $arrEstadoActual["ALDIA"] = 0;
      $arrEstadoActual["PREMORA"] = 0;
      $arrEstadoActual["ENMORA"] = 0;
      $arrEstadoActual["CANCELADO"] = 0;
      ?>
      <div id="no-more-tables">
        <?php 
        if( $boolDetalle ){
          ?>
          <table class="table table-sm table-hover table-striped">
            <thead class="cf">
              <tr>
                <th>No.</th>
                <th>Usuario</th>
                <th>Préstamo</th>
                <th>Descripción</th>
                <th>Fecha</th>
                <th>Clasificación</th>
                <th>Estado</th>
                <th>Saldo</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $intCount = 0;
              $sumSaldoCapital = 0;
              reset($arrDetail);
              while( $rTMP = each($arrDetail) ){
                $intCount++;
                $strUsuario = $rTMP["value"]["USUARIO"];
                $strPrestamo = $rTMP["value"]["PRESTAMO"];
                $strDescripcion = $rTMP["value"]["DESCRIPCION"];

                $deSaldoCapital = $rTMP["value"]["SALDO_CAPITAL"];
                if( $deSaldoCapital == 0 ){
                  $deSaldoCapital = $this->objModel->getSaldoListado($strPrestamo);
                }
                $sumSaldoCapital =  $sumSaldoCapital + $deSaldoCapital;

                $strFecha = $rTMP["value"]["FECHA"];
                $strClasificacion = utf8_encode($rTMP["value"]["CLASIFICACION"]);
                $strEstado = $rTMP["value"]["ESTADO_ACTUAL"];

                if( $strEstado == '' ){
                  $strEstado = $this->objModel->getEstadoActual($strPrestamo);
                }

                switch ($strEstado) {
                  case "Premora":
                    $arrEstadoActual["PREMORA"] = $arrEstadoActual["PREMORA"] + 1;
                    break;
                  case "Al dia":
                    $arrEstadoActual["ALDIA"] = $arrEstadoActual["ALDIA"] + 1;
                    break;
                  case "Cancelado":
                    $arrEstadoActual["CANCELADO"] = $arrEstadoActual["CANCELADO"] + 1;
                    break;
                  case "En mora":
                    $arrEstadoActual["ENMORA"] = $arrEstadoActual["ENMORA"] + 1;
                    break;
                }
                
                ?>
                <tr>
                  <td data-title="No.">
                    <h5><span class="badge bg-light-blue"><?php print $intCount;?></span></h5>
                  </td>
                  <td data-title="Usuario" style="vertical-align:middle;"><?php print $strUsuario;?></td>
                  <td data-title="Préstamo" style="vertical-align:middle;"><?php print $strPrestamo;?></td>
                  <td data-title="Descripción" style="vertical-align:middle;"><?php print ($boolExport)? utf8_decode($strDescripcion): $strDescripcion;?></td>
                  <td data-title="Fecha" style="vertical-align:middle;"><?php print $strFecha;?></td>
                  <td data-title="Clasificación" style="vertical-align:middle;"><?php print $strClasificacion;?></td>
                  <td data-title="Estado" style="vertical-align:middle;"><?php print $strEstado;?></td>
                  <td data-title="Saldo" style="vertical-align:middle;"><?php print 'Q.'.number_format($deSaldoCapital, 2, '.', ',');?></td>
                </tr>
                <?php
              }
              ?>
              <tr>
                <td colspan="7">&nbsp;</td>
                <td colspan="1" data-title="Total Saldo.."><b><?php print 'Q.'.number_format($sumSaldoCapital, 2, '.', ',');?></b></td>
              </tr>
            <tbody>
          </table>
          <?php
        }else{
          $arrDetail2 = $arrDetail;
          reset($arrDetail2);
          while( $rTMP = each($arrDetail2) ){
            $strPrestamo = $rTMP["value"]["PRESTAMO"];
            $strEstado = $rTMP["value"]["ESTADO_ACTUAL"];
            if( $strEstado == '' ){
              $strEstado = $this->objModel->getEstadoActual($strPrestamo);
            }

            switch ($strEstado){
              case "Premora":
                $arrEstadoActual["PREMORA"] = $arrEstadoActual["PREMORA"] + 1;
                break;
              case "Al dia":
                $arrEstadoActual["ALDIA"] = $arrEstadoActual["ALDIA"] + 1;
                break;
              case "Cancelado":
                $arrEstadoActual["CANCELADO"] = $arrEstadoActual["CANCELADO"] + 1;
                break;
              case "En mora":
                $arrEstadoActual["ENMORA"] = $arrEstadoActual["ENMORA"] + 1;
                break;
            }
          }
        }
        ?>
        <br><br><br>
        <?php
        $intSumaCasos = $arrEstadoActual["ALDIA"] + $arrEstadoActual["CANCELADO"] +  $arrEstadoActual["ENMORA"] +  $arrEstadoActual["PREMORA"];
        ?>
        <table class="table table-sm table-hover">
          <tr>
            <td colspan="4" style="background-color: #20b4c5; color:white; text-align:center;"><h1>Estadisticas por Estado Actual del Préstamo</h1></td>
          </tr>
          <tr>
            <td style="text-align:center;"><h4><b>No.</b></h4></td>
            <td style="text-align:center;"><h4><b>Estado</b></h4></td>
            <td style="text-align:center;"><h4><b>Porcentaje</b></h4></td>
            <td style="text-align:center;"><h4><b>Recuento</b></h4></td>
          </tr>
          <tr>
            <td style="text-align:center;"><h4>1</h4></td>
            <td style="text-align:center;"><h4>Al dia</h4></td>
            <td style="text-align:center;">
              <h4>
              <?php 
              $intPorcentajeAldia = ($arrEstadoActual["ALDIA"]/$intSumaCasos)*100;
              $intPorcentajeAldia = number_format($intPorcentajeAldia, 2);
              print $intPorcentajeAldia.'%';
              ?>
              </h4>
            </td>
            <td style="text-align:center;"><h4><?php print $arrEstadoActual["ALDIA"];?></h4></td>
          </tr>
          <tr>
            <td style="text-align:center;"><h4>2</h4></td>
            <td style="text-align:center;"><h4>Cancelado</h4></td>
            <td style="text-align:center;">
              <h4>
              <?php 
              $intPorcentajeCancelados = ($arrEstadoActual["CANCELADO"]/$intSumaCasos)*100;
              $intPorcentajeCancelados = number_format($intPorcentajeCancelados, 2);
              print $intPorcentajeCancelados.'%';
              ?>
              </h4>
            </td>
            <td style="text-align:center;"><h4><?php print $arrEstadoActual["CANCELADO"];?></h4></td>
          </tr>
          <tr>
            <td style="text-align:center;"><h4>3</h4></td>
            <td style="text-align:center;"><h4>En mora</h4></td>
            <td style="text-align:center;">
              <h4>
              <?php 
              $intPorcentajeEnmora = ($arrEstadoActual["ENMORA"]/$intSumaCasos)*100;
              $intPorcentajeEnmora = number_format($intPorcentajeEnmora, 2);
              print $intPorcentajeEnmora.'%';
              ?>
              </h4>
            </td>
            <td style="text-align:center;"><h4><?php print $arrEstadoActual["ENMORA"];?></h4></td>
          </tr>
          <tr>
            <td style="text-align:center;"><h4>4</h4></td>
            <td style="text-align:center;"><h4>Premora</h4></td>
            <td style="text-align:center;">
              <h4>
              <?php 
              $intPorcentajePremora = ($arrEstadoActual["PREMORA"]/$intSumaCasos)*100;
              $intPorcentajePremora = number_format($intPorcentajePremora, 2);
              print $intPorcentajePremora.'%';
              ?>
              </h4>
            </td>
            <td style="text-align:center;"><h4><?php print $arrEstadoActual["PREMORA"];?></h4></td>
          </tr>
          <tr>
            <td style="text-align:right;">&nbsp;</td>
            <td style="text-align:right;"><h4><b>Total..</b></h4></td>
            <td style="text-align:center;"><h4><b>100%</b></h4></td>
            <td style="text-align:center;">
              <h4><b><?php print $intSumaCasos; ?></b></h4>
            </td>
          </tr>
        </table>
        <br><br><br>
        <?php 
        if( count($arrConteo)>0 ){
        ?>
          <table class="table table-sm table-hover">
              <tr>
                <td colspan="3" style="background-color: #20b4c5; color:white; text-align:center;"><h1>Estadisticas por Clasificación de Cobro</h1></td>
              </tr>
              <?php 
              $arrTMP = $arrConteo;
              $intTotalRecuentoTMP = 0;
              reset($arrTMP);
              while( $cTMP = each($arrTMP) ){
                reset($cTMP["value"]["SUBCATEGORIA"]);
                while( $cTMP2 = each($cTMP["value"]["SUBCATEGORIA"]) ){
                  $intRecuentoTMP = $cTMP2["value"]["CONTEO"];
                  $intTotalRecuentoTMP = $intTotalRecuentoTMP + $intRecuentoTMP;
                }
              }


              $intTotalRecuento = 0;
              reset($arrConteo);
              while( $sTMP = each($arrConteo) ){
                $strCategoria = utf8_encode($sTMP["key"]);
                ?>
                <tr>
                  <td colspan="3" style="background-color: #0fcfe5; color:white; text-align:center;"><h2><?php print $strCategoria;?></h2></td>
                </tr>
                <tr>
                  <td style="text-align:center;"><h4><b>Subcategoria</b></h4></td>
                  <td style="text-align:center;"><h4><b>Porcentaje</b></h4></td>
                  <td style="text-align:center;"><h4><b>Recuento</b></h4></td>
                </tr>
                <?php
                reset($sTMP["value"]["SUBCATEGORIA"]);
                while( $sTMP2 = each($sTMP["value"]["SUBCATEGORIA"]) ){
                  $strSubCategoria = utf8_encode($sTMP2["key"]);
                  $intRecuento = $sTMP2["value"]["CONTEO"];
                  $intPorcentaje = ($intRecuento/$intTotalRecuentoTMP)*100;
                  $intPorcentaje = number_format($intPorcentaje, 2);
                  $intTotalRecuento = $intTotalRecuento + $intRecuento;
                  ?>
                  <tr>
                    <td style="text-align:center;"><h4><?php print $strSubCategoria;?></h4></td>
                    <td style="text-align:center;"><?php print $intPorcentaje.'%';?></td>
                    <td style="text-align:center;"><h4><?php print $intRecuento;?></h4></td> 
                  </tr>
                  <?php
                }
              }
              ?>
              <tr>
                <td style="text-align:right;"><h4><b>Total..</b></h4></td>
                <td style="text-align:center;"><h4><b>100%</b></h4></td>
                <td style="text-align:center;"><h4><b><?php print $intTotalRecuento;?></b></h4></td>
              </tr>
          </table>
        <?php 
        }
        ?>
      </div>
      <?php
    }else{
      ?>
      <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
          <h3>No se encontraron resultados.</h3>
        </div>
      </div>
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
          <?php draMenu($this->arrRolUser,'rpt_gestiones', 3); ?>
        </section>
        <!-- /.sidebar -->
      </aside>

      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <br>
        <section class="content-header">
          <h1>Reporte Gestiones de Pago</h1>
        </section>
        <!-- Main content -->
        <section class="content">
          <!-- Small boxes (Stat box) -->
          <div class="row">
            <div class="box box-info content">
              <div class="box-header content">
                <form id="frmFiltros" method="post"></form>
                <div id="divFiltros">
                  <div class="row">
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" style="text-align:center; vertical-align:middle;"><h4><b>Usuario</b></h4></div>
                    <div class="col-xs-12 col-sm-9 col-md-9 col-lg-9" style="text-align:center; vertical-align:middle;"><?php $this->drawSelectUsuarios(); ?></div>
                  </div>
                  <br>
                  <div class="row">
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" style="text-align:center; vertical-align:middle;"><h4><b>Tipo Riesgo</b></h4></div>
                    <div class="col-xs-12 col-sm-9 col-md-9 col-lg-9" style="text-align:center; vertical-align:middle;"><?php $this->drawSelectTipoRiesgo(); ?></div>
                  </div>
                  <br>
                  <div class="row">
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" style="text-align:center; vertical-align:middle;"><h4><b>Clasificacion</b></h4></div>
                    <div class="col-xs-12 col-sm-9 col-md-9 col-lg-9" style="text-align:center; vertical-align:middle;"><?php $this->drawSelectClasificacion(); ?></div>
                  </div>
                  <br>
                  <div class="row">
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" style="text-align:center; vertical-align:middle;"><h4><b>Fecha Inicial</b></h4></div>
                    <div class="col-xs-12 col-sm-9 col-md-9 col-lg-9" style="text-align:center; vertical-align:middle;">
                      <input type="date" class="form-control" id="txtFechaInicio" name="txtFechaInicio" style="text-align:center;">
                    </div>
                  </div>
                  <br>
                  <div class="row">
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" style="text-align:center; vertical-align:middle;"><h4><b>Fecha Final</b></h4></div>
                    <div class="col-xs-12 col-sm-9 col-md-9 col-lg-9" style="text-align:center; vertical-align:middle;">
                      <input type="date" class="form-control" id="txtFechaFinal" name="txtFechaFinal" style="text-align:center;">
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" style="text-align:center; vertical-align:middle;"><h4><b>¿ Mostrar detalle ?</b></h4></div>
                    <div class="col-xs-12 col-sm-9 col-md-9 col-lg-9" style="text-align:center; vertical-align:middle;">
                      <select id="sltMostrarDetalle" name="sltMostrarDetalle" class="form-control" style="text-align: center;">
                        <option value="Y">SI</option>
                        <option value="N">NO</option>
                      </select>
                    </div>
                  </div>
                  <br>
                  <div class="row">
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" style="text-align:center; vertical-align:middle;">
                      <button id="btngenerarreporte" class="btn btn-success btn-raised btn-block" onclick="getDetailReporte()">Generar Reporte</button>
                    </div>
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" style="text-align:center; vertical-align:middle;">
                      <button class="btn btn-success btn-raised btn-block" id="btnExportarExcel" onclick="fntExportarData('EXCEL')" style="display:none;">Exportar a Excel</button>
                    </div>
                  </div>
                </div>
              </div>
              <br><br>
              <div id="divShowLoadingGeneralBig" style="display:none;" class='centrar'><img src="images/loading.gif" height="300px" width="300px"></div>
              <!-- Detalle -->
              <div class="box-body" id="divDetalle">
              </div>
              <!-- Detalle -->
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
                    url:"rpt_gestiones.php",
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


        function getDetailReporte(){
          var boolError = false;

          //Captar fecha inicial en JS
          var fechainicio = new Date($('#txtFechaInicio').val());
          var diasfi = 1; // Número de días a agregar
          fechainicio.setDate(fechainicio.getDate() + diasfi);

          //Captar fecha final en JS
          var fechafinal = new Date($('#txtFechaFinal').val());
          var diasff = 1; // Número de días a agregar
          fechafinal.setDate(fechafinal.getDate() + diasff);

          if( (fechafinal < fechainicio) || ( isNaN(fechainicio) ) || ( isNaN(fechafinal) )){
              boolError = true;
              $("#txtFechaInicio").css('background-color','#d2f7e4');
              $("#txtFechaFinal").css('background-color','#d2f7e4');
          }else{
              $("#txtFechaInicio").css('background-color','#ffffff');
              $("#txtFechaFinal").css('background-color','#ffffff');
          }


          if( boolError == false ){
            var objSerialized = $("#divFiltros").find("select, input").serialize();
            $.ajax({
              url:"rpt_gestiones.php",
              data: objSerialized,
              type:"post",
              dataType: "html",
              beforeSend: function() {
                $("#divDetalle").html('');
                $("#divShowLoadingGeneralBig").css("z-index", 1050);
                $("#divShowLoadingGeneralBig").show();
                $("#btngenerarreporte").prop('disabled', true);
                $("#btnExportarExcel").prop('disabled', true);
              },
              success:function(data){
                $("#divDetalle").html('');
                $("#divDetalle").html(data);
                $("#divShowLoadingGeneralBig").hide();
                $("#btngenerarreporte").prop('disabled', false);
                $("#btnExportarExcel").prop('disabled', false);
              }
            });
          }
          
        }

        function addHidden(theForm, key, value) {
            // Create a hidden input element, and append it to the form:
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;'name-as-seen-at-the-server';
            input.value = value;
            theForm.appendChild(input);
        }

        //Permite enviar la peticion para poder exportar el reporte en PDF o EXCEL
        function fntExportarData(strTipo){
            var intUsuarios = $("#selectUsuarios").val();
            var intTipoRiesgo = $("#selectTipoRiesgo").val();
            var intSubcategoria = $("#sltClasificacion").val();
            var strMostrarDetalle = $("#sltMostrarDetalle").val();

            //Captar fecha inicial en JS
            var fechainicio = $('#txtFechaInicio').val();

            //Captar fecha final en JS
            var fechafinal = $('#txtFechaFinal').val();

            var objForm = document.getElementById("frmFiltros");
            objForm.target = "_self";
            addHidden(objForm, 'hdnExportar', 'true');
            addHidden(objForm, 'TipoExport', strTipo);
            addHidden(objForm, 'intUsuarios', intUsuarios);
            addHidden(objForm, 'intTipoRiesgo', intTipoRiesgo);
            addHidden(objForm, 'sltClasificacion', intSubcategoria);
            addHidden(objForm, 'strFechaInicial', fechainicio);
            addHidden(objForm, 'strFechaFinal', fechafinal);
            addHidden(objForm, 'sltMostrarDetalle', strMostrarDetalle);
            objForm.submit();

        }

        $(document).ready( function() {
            var now = new Date();
            var month = (now.getMonth() + 1);               
            var day = now.getDate();
            if (month < 10) 
                month = "0" + month;
            if (day < 10) 
                day = "0" + day;
            var firsdaymonth = now.getFullYear() + '-' + month + '-' + '01';
            var today = now.getFullYear() + '-' + month + '-' + day;
            $('#txtFechaInicio').val(firsdaymonth);
            $('#txtFechaFinal').val(today);
        });

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



