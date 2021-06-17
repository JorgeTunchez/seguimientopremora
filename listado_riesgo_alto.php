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
    }elseif( $strRolUserSession == "mora" ){
      $arrRolUser["MORA"] = true;
    }
  }
}else{
  header("Location: index.php");
}

$objController = new lra_controller($arrRolUser);
$objController->runAjax();
$objController->drawContentController();

class lra_controller{

  private $objModel;
  private $objView;
  private $arrRolUser;
  
  public function __construct($arrRolUser){
    $this->objModel = new lra_model($arrRolUser);
    $this->objView = new lra_view($arrRolUser);
    $this->arrRolUser = $arrRolUser;
  }

  public function drawContentController(){
    $this->objView->drawContent(); 
  }

  public function runAjax(){
    $this->ajaxDestroySession();
    $this->ajaxgetDetailPrestamo();
    $this->ajaxSavePromesaPago();
    $this->ajaxGetPromesasPago();
    $this->ajaxsaveEditDatosAsoc();
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

  public function ajaxsaveEditDatosAsoc(){
    if( isset($_POST["saveEditDatosAsoc"]) && $_POST["saveEditDatosAsoc"] == "true" ){
      header("Content-Type: application/html;");
      $streditCIF = isset($_POST["editCIF"])? $_POST["editCIF"]: "";
      $streditAsocCelular = isset($_POST["editAsocCelular"])? $_POST["editAsocCelular"]: "";
      $streditAsocTelCasa = isset($_POST["editAsocTelCasa"])? $_POST["editAsocTelCasa"]: "";
      $streditAsocTelOfi = isset($_POST["editAsocTelOfi"])? $_POST["editAsocTelOfi"]: "";
      $streditAsocRP1 = isset($_POST["editAsocRP1"])? $_POST["editAsocRP1"]: "";
      $streditAsocRP1Tel = isset($_POST["editAsocRP1Tel"])? $_POST["editAsocRP1Tel"]: "";
      $streditAsocRP2 = isset($_POST["editAsocRP2"])? $_POST["editAsocRP2"]: "";
      $streditAsocRP2Tel = isset($_POST["editAsocRP2Tel"])? $_POST["editAsocRP2Tel"]: "";
      $streditAsocRL1 = isset($_POST["editAsocRL1"])? $_POST["editAsocRL1"]: "";
      $streditAsocRL1Tel = isset($_POST["editAsocRL1Tel"])? $_POST["editAsocRL1Tel"]: "";
      $streditAsocRL2 = isset($_POST["editAsocRL2"])? $_POST["editAsocRL2"]: "";
      $streditAsocRL2Tel = isset($_POST["editAsocRL2Tel"])? $_POST["editAsocRL2Tel"]: "";

      $this->objModel->ajaxsaveEditDatosAsoc($streditCIF, $streditAsocCelular, $streditAsocTelCasa, $streditAsocTelOfi, 
      $streditAsocRP1, $streditAsocRP1Tel, $streditAsocRP2, $streditAsocRP2Tel, $streditAsocRL1, $streditAsocRL1Tel, $streditAsocRL2, $streditAsocRL2Tel, $this->arrRolUser["ID"]);
      print $this->objView->drawRowsDatosAsociado($streditCIF, $streditAsocCelular, $streditAsocTelCasa, $streditAsocTelOfi, $streditAsocRP1, $streditAsocRP1Tel, $streditAsocRP2, $streditAsocRP2Tel, $streditAsocRL1, $streditAsocRL1Tel, $streditAsocRL2, $streditAsocRL2Tel);
      exit();
    }
  }

  public function ajaxgetDetailPrestamo(){
    if( isset($_POST["getDetailPrestamo"]) && $_POST["getDetailPrestamo"] == "true"){
      $intPrestamo = isset($_POST["prestamo"])? intval($_POST["prestamo"]): 0;
      $arrDetail = $this->objModel->getDetallePrestamo($intPrestamo);
      print $this->objView->drawBlurDetailContent($arrDetail);
      exit();
    }
  }

  public function ajaxSavePromesaPago(){
    if( isset($_POST["savePromesaPago"]) && $_POST["savePromesaPago"] == "true"){
      header("Content-Type: application/json;");

      //Datos de la gestion
      $intPrestamo = isset($_POST["prestamo"])? intval($_POST["prestamo"]): 0;
      $strDescripcion = isset($_POST["descripcion"])? $_POST["descripcion"]: "";
      $intSubcategoria = isset($_POST["categoria"])? intval($_POST["categoria"]): 0;

      //Datos de la imagen
      if( isset($_FILES['txtimagen']['tmp_name']) ){
        //
        $fileTmpPath = $_FILES['txtimagen']['tmp_name'];
        $fileName = $_FILES['txtimagen']['name'];
        $fileType = $_FILES['txtimagen']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        $newFileName = date("YmdHis").'.'.$fileExtension;
        $this->objModel->uploadFile($fileTmpPath, $newFileName, $fileExtension);
      }else{
        $newFileName = "";
      }

      $result = $this->objModel->insertPromesaPago($intPrestamo, $strDescripcion, $intSubcategoria, $newFileName, $this->arrRolUser["ID"]);
      $arrReturn["Correcto"] = $result;
      print json_encode($arrReturn);
      exit();
    }
  }

  public function ajaxGetPromesasPago(){
    if( isset($_POST["getPromesasPago"]) && $_POST["getPromesasPago"] == "true"){
      $intPrestamo = isset($_POST["prestamo"])? intval($_POST["prestamo"]): 0;
      $arrDetail = $this->objModel->getListadoPromesas($intPrestamo);
      print $this->objView->drawBlurContentPromesas($arrDetail);
      exit();
    }
  }

}

class lra_model{

  private $arrRolUser;

	public function __construct($arrRolUser){
    $this->arrRolUser = $arrRolUser;
  }

  function boolExisteUpdate($streditCIF){
    if( $streditCIF != '' ){
      $boolExisteUpdate = false;
      $conn = getConexion();
      $strQuery = "SELECT id FROM update_asociado WHERE cif = '{$streditCIF}'";
      $result = mysqli_query($conn, $strQuery);
      if( !empty($result) ){  
        while($row = mysqli_fetch_assoc($result)) {
          $intId = $row["id"];
          $boolExisteUpdate = ($intId>0)? true: false;
        }
      }

      mysqli_close($conn);
      return $boolExisteUpdate;
    }
  }

  public function ajaxsaveEditDatosAsoc($streditCIF, $streditAsocCelular, $streditAsocTelCasa, $streditAsocTelOfi, $streditAsocRP1, $streditAsocRP1Tel, $streditAsocRP2, $streditAsocRP2Tel, $streditAsocRL1, $streditAsocRL1Tel, $streditAsocRL2, $streditAsocRL2Tel, $intUser){
    if( $streditCIF != ''){
      $conn = getConexion();
      $boolExisteUpdate = $this->boolExisteUpdate($streditCIF);
      if( $boolExisteUpdate ){
        $strQuery = "UPDATE update_asociado
                        SET celular = '{$streditAsocCelular}',
                            telefono_casa = '{$streditAsocTelCasa}',
                            telefono_oficina = '{$streditAsocTelOfi}',
                            ref_personal_1 = '{$streditAsocRP1}',
                            ref_personal_1_tel = '{$streditAsocRP1Tel}',
                            ref_personal_2 = '{$streditAsocRP2}',
                            ref_personal_2_tel = '{$streditAsocRP2Tel}',
                            ref_laboral_1 = '{$streditAsocRL1}',
                            ref_laboral_1_tel = '{$streditAsocRL1Tel}',
                            ref_laboral_2 = '{$streditAsocRL2}',
                            ref_laboral_2_tel = '{$streditAsocRL2Tel}',
                            mod_user = {$intUser},
                            mod_fecha = NOW()
                      WHERE cif = '{$streditCIF}'";
        mysqli_query($conn, $strQuery);
      }else{
        $strQuery = "INSERT INTO update_asociado(cif, 
                                                 celular, 
                                                 telefono_casa, 
                                                 telefono_oficina, 
                                                 ref_personal_1, 
                                                 ref_personal_1_tel, 
                                                 ref_personal_2, 
                                                 ref_personal_2_tel, 
                                                 ref_laboral_1, 
                                                 ref_laboral_1_tel, 
                                                 ref_laboral_2, 
                                                 ref_laboral_2_tel, 
                                                 add_user, 
                                                 add_fecha) 
                                          VALUES ({$streditCIF}, 
                                                 '{$streditAsocCelular}', 
                                                 '{$streditAsocTelCasa}', 
                                                 '{$streditAsocTelOfi}', 
                                                 '{$streditAsocRP1}', 
                                                 '{$streditAsocRP1Tel}',
                                                 '{$streditAsocRP2}',
                                                 '{$streditAsocRP2Tel}', 
                                                 '{$streditAsocRL1}', 
                                                 '{$streditAsocRL1Tel}', 
                                                 '{$streditAsocRL2}', 
                                                 '{$streditAsocRL2Tel}', 
                                                 {$intUser}, 
                                                 NOW())";
        mysqli_query($conn, $strQuery);
      }
      mysqli_close($conn);
      return "Y";
    }
  }

  function getDetallePrestamo($intPrestamo){

    if( $intPrestamo>0 ){
      $conn = getConexion();
      $arrDetail = array();

      $strCIF = getCifByPrestamo($intPrestamo);
      $boolExisteUpdate = $this->boolExisteUpdate($strCIF);
      $strInnerJoinUA = ($boolExisteUpdate)? "INNER JOIN update_asociado ON asociado.cif = update_asociado.cif":"";
      
      if( $boolExisteUpdate ){
        $strCampoAsociado ="update_asociado.cif, 
                            asociado.nombres, 
                            update_asociado.celular, 
                            update_asociado.telefono_casa, 
                            update_asociado.telefono_oficina,
                            update_asociado.ref_personal_1,
                            update_asociado.ref_personal_1_tel,
                            update_asociado.ref_personal_2,
                            update_asociado.ref_personal_2_tel,
                            update_asociado.ref_laboral_1,
                            update_asociado.ref_laboral_1_tel,
                            update_asociado.ref_laboral_2,
                            update_asociado.ref_laboral_2_tel,";
      }else{
        $strCampoAsociado ="asociado.cif, 
                            asociado.nombres, 
                            asociado.celular, 
                            asociado.telefono_casa, 
                            asociado.telefono_oficina,
                            asociado.ref_personal_1,
                            asociado.ref_personal_1_tel,
                            asociado.ref_personal_2,
                            asociado.ref_personal_2_tel,
                            asociado.ref_laboral_1,
                            asociado.ref_laboral_1_tel,
                            asociado.ref_laboral_2,
                            asociado.ref_laboral_2_tel,";
      }

      $strQuery = "SELECT prestamo.id idprestamo, 
                          agencias.nombre nombreagencia,
                          prestamo.numero, 
                          prestamo.estado_prestamo, 
                          DATE_FORMAT(prestamo.fecha_primer_desembolso, '%d/%m/%Y') fecha_primer_desembolso,
                          DATE_FORMAT(prestamo.fecha_proximo_pago, '%d/%m/%Y') fecha_proximo_pago,
                          DATE_FORMAT(prestamo.fecha_ultimo_pago, '%d/%m/%Y') fecha_ultimo_pago,
                          prestamo.dias_mora_capital, 
                          prestamo.saldo_capital, 
                          prestamo.capital_vencido, 
                          prestamo.saldo_interes, 
                          prestamo.monto_mora,
                          {$strCampoAsociado}
                          prestamo.capital_desembolsado,
                          prestamo.garantia
                     FROM prestamo 
                          INNER JOIN asociado 
                                  ON prestamo.asociado = asociado.id 
                          {$strInnerJoinUA}
                          INNER JOIN agencias
                                  ON prestamo.agenciacodigo = agencias.codigo
                    WHERE prestamo.id = {$intPrestamo}";
      $result = mysqli_query($conn, $strQuery);
      if( !empty($result) ){
        while($row = mysqli_fetch_assoc($result)) {
          $arrDetail["IDPRESTAMO"] = $row["idprestamo"];
          $arrDetail["NOMBREAGENCIA"] = $row["nombreagencia"];
          $arrDetail["NUMERO"] = $row["numero"];
          $arrDetail["ESTADO_PRESTAMO"] = $row["estado_prestamo"];
          $arrDetail["FECHA_PRIMER_DESEMBOLSO"] = $row["fecha_primer_desembolso"];
          $arrDetail["FECHA_PROXIMO_PAGO"] = $row["fecha_proximo_pago"];
          $arrDetail["FECHA_ULTIMO_PAGO"] = $row["fecha_ultimo_pago"];
          $arrDetail["DIAS_MORA_CAPITAL"] = $row["dias_mora_capital"];
          $arrDetail["SALDO_CAPITAL"] = $row["saldo_capital"];
          $arrDetail["CAPITAL_VENCIDO"] = $row["capital_vencido"];
          $arrDetail["SALDO_INTERES"] = $row["saldo_interes"];
          $arrDetail["MONTO_MORA"] = $row["monto_mora"];
          $arrDetail["CIF"] = $row["cif"];
          $arrDetail["NOMBRES"] = $row["nombres"];
          $arrDetail["CELULAR"] = $row["celular"];
          $arrDetail["TELEFONO_CASA"] = $row["telefono_casa"];
          $arrDetail["TELEFONO_OFICINA"] = $row["telefono_oficina"];
          $arrDetail["RP1_NOMBRE"] = $row["ref_personal_1"];
          $arrDetail["RP1_TELFONO"] = $row["ref_personal_1_tel"];
          $arrDetail["RP2_NOMBRE"] = $row["ref_personal_2"];
          $arrDetail["RP2_TELFONO"] = $row["ref_personal_2_tel"];
          $arrDetail["RL1_NOMBRE"] = $row["ref_laboral_1"];
          $arrDetail["RL1_TELEFONO"] = $row["ref_laboral_1_tel"];
          $arrDetail["RL2_NOMBRE"] = $row["ref_laboral_2"];
          $arrDetail["RL2_TELEFONO"] = $row["ref_laboral_2_tel"];
          $arrDetail["GARANTIA"] = $row["garantia"];
          $arrDetail["CUOTA_CERO"] = ( $row["capital_desembolsado"] == $row["saldo_capital"] )? "SI":"NO";
        }
      }

      mysqli_close($conn);
      return $arrDetail;
    }

  }

  function getListadoRiesgoAlto(){
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];
    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true)? "AND prestamo.agenciacodigo IN ({$intAgencia})":"";
    
    if( isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true ){
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    }elseif( isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true ){
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 "; 
    }else{
      $strAndDiasMora = "";
    }
    
    $arrListadoRA = array();
    $strQuery = "SELECT prestamo.id, 
                        agencias.nombre nombreagencia,
                        prestamo.numero, 
                        asociado.nombres,
                        prestamo.saldo_capital,
                        prestamo.dias_mora_capital,
                        prestamo.capital_desembolsado,
                        (SELECT COUNT(id) conteo FROM promesa_pago WHERE prestamo = prestamo.numero AND DATE_FORMAT(add_fecha, '%m') = DATE_FORMAT(NOW(), '%m')) promesas
                   FROM prestamo 
                        INNER JOIN asociado 
                                ON prestamo.asociado = asociado.id 
                        INNER JOIN agencias
                                ON prestamo.agenciacodigo = agencias.codigo
                  WHERE prestamo.saldo_capital BETWEEN (SELECT rango_inicial FROM estado_premora WHERE id = 3) AND (SELECT rango_final FROM estado_premora WHERE id = 3) 
                        {$strAndDiasMora}
                        {$strAndAgenciaCodigo}
                  ORDER BY promesas ASC, prestamo.saldo_capital DESC";
    $result = mysqli_query($conn, $strQuery);
    if( !empty($result) ){  
      while($row = mysqli_fetch_assoc($result)) {
        $arrListadoRA[$row["id"]]["NUMERO"] = $row["numero"];
        $arrListadoRA[$row["id"]]["AGENCIA"] = $row["nombreagencia"];
        $arrListadoRA[$row["id"]]["NOMBRES"] = $row["nombres"];
        $arrListadoRA[$row["id"]]["SALDO_CAPITAL"] = $row["saldo_capital"];
        $arrListadoRA[$row["id"]]["DIAS_MORA_CAPITAL"] = $row["dias_mora_capital"];
        $arrListadoRA[$row["id"]]["CAPITAL_DESEMBOLSADO"] = $row["capital_desembolsado"];
        $arrListadoRA[$row["id"]]["PROMESAS"] = $row["promesas"];
      }
    }

    mysqli_close($conn);
    return $arrListadoRA;
  }

  public function insertPromesaPago($intPrestamo, $strDescripcionPromesa, $intSubcategoria, $strImagen, $intUser){
    if( $intPrestamo>0 && $strDescripcionPromesa!='' && $intSubcategoria>0 && $intUser>0 ){
      $conn = getConexion();
      $strImagen = ($strImagen !='')? "'".$strImagen."'": "NULL"; 
      $strQuery = "INSERT INTO promesa_pago (prestamo, descripcion, subcategoria_gestion, imagen, add_user, add_fecha) 
                   VALUES ({$intPrestamo},'{$strDescripcionPromesa}', {$intSubcategoria}, {$strImagen},{$intUser}, NOW())";
      mysqli_query($conn, $strQuery);
      mysqli_close($conn);
      return "Y";
    }
  }

  public function getListadoPromesas($intPrestamo){
    if( $intPrestamo>0 ){
      $arrListadoPromesas = array();
      $conn = getConexion();
      $strQuery = "SELECT promesa_pago.id, 
                          promesa_pago.descripcion, 
                          DATE_FORMAT(promesa_pago.add_fecha, '%d/%m/%Y %h:%m:%s %p') add_fecha,
                          usuarios.nombre nombreusuario,
                          subcategorias_gestiones.nombre nombre_subcategoria,
                          promesa_pago.imagen
                     FROM promesa_pago
                          INNER JOIN usuarios 
                                  ON promesa_pago.add_user = usuarios.id 
                          INNER JOIN prestamo
                                  ON promesa_pago.prestamo = prestamo.numero
                          LEFT JOIN subcategorias_gestiones
                                  ON promesa_pago.subcategoria_gestion = subcategorias_gestiones.id
                    WHERE prestamo.numero = {$intPrestamo}
                    ORDER BY promesa_pago.add_fecha DESC";
      $result = mysqli_query($conn, $strQuery);
      if( !empty($result) ){  
        while($row = mysqli_fetch_assoc($result)) {
          $arrListadoPromesas[$row["id"]]["DESCRIPCION"] = $row["descripcion"];
          $arrListadoPromesas[$row["id"]]["ADD_FECHA"] = $row["add_fecha"];
          $arrListadoPromesas[$row["id"]]["USUARIO"] = $row["nombreusuario"];
          $arrListadoPromesas[$row["id"]]["SUBCATEGORIA"] = $row["nombre_subcategoria"];
          $arrListadoPromesas[$row["id"]]["IMAGEN"] = $row["imagen"];
        }
      }

      mysqli_close($conn);
      return $arrListadoPromesas;
    }
  }

  public function uploadFile($fileTmpPath, $newFileName, $fileExtension){
    $allowedfileExtensions = array('jpg', 'jpeg', 'gif', 'png', 'bmp');
    if (in_array($fileExtension, $allowedfileExtensions)) {
        $uploadFileDir = 'images_gestion/';
        $dest_path = $uploadFileDir . $newFileName;
        move_uploaded_file($fileTmpPath, $dest_path);
    }
  }

}

class lra_view{

  private $objModel;
  private $arrRolUser;

	public function __construct($arrRolUser){
    $this->objModel = new lra_model($arrRolUser);
    $this->arrRolUser = $arrRolUser;
  }

  public function drawBlurDetail(){
    ?>
    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="modalContentDetail">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header" style="background-color: #dd4b39; color:white; text-align:center;">
            <h1 class="modal-title">Detalle del préstamo</h1>
          </div>
          <div class="modal-body" id="divContentModalDetail"></div>
          <div class="modal-footer">
            <button type="button" id="btnsavePromesaPago" class="btn btn-primary btn-raised" onclick="savePromesaPago()">Guardar</button>
            <button type="button" class="btn btn-primary btn-raised" onclick="openModalPromesasdePago()">Bitácora</button>
            <button type="button" class="btn btn-secondary btn-raised" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
    <?php
  }

  public function drawSelectClasificacion(){
    $arrSubCategorias = getSubcategoriasGestiones();
    ?>
    <select id="sltClasificacion" name="sltClasificacion" class="form-control" style="text-align: center;">
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

  public function drawRowsDatosAsociado($intCIF, $strCelular, $strTelefonoCasa, $strTelefonoOficina, $strRP1Nombre, $strRP1Telefono, $strRP2Nombre, $strRP2Telefono, $strRL1Nombre, $strRL1Telefono, $strRL2Nombre, $strRL2Telefono ){
    ?>
    <table class="table">
    <tr>
      <td colspan="2">
      <b>CIF</b>: <?php print $intCIF;?>
      <input type="hidden" id="hdnCIF" value="<?php print $intCIF;?>">
      </td>
      <td colspan="2">
        <div id="divShowCelular">
          <b>Celular</b>: <?php print $strCelular;?>
        </div>
        <div id="divEditCelular" style="display:none;">
          <b>Celular</b>:
          <input class="form-control" type="text" id="txtAsocCelular" name="txtAsocCelular" value="<?php print $strCelular;?>">
        </div>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <div id="divShowTelefonoCasa">
          <b>Teléfono Casa</b>: <?php print $strTelefonoCasa;?>
        </div>
        <div id="divEditTelefonoCasa" style="display:none;">
          <b>Teléfono Casa</b>:
          <input class="form-control" type="text" id="txtAsocTelCasa" name="txtAsocTelCasa" value="<?php print $strTelefonoCasa;?>">
        </div>
      <td colspan="2">
        <div id="divShowTelefonoOficina">
          <b>Teléfono Oficina</b>: <?php print $strTelefonoOficina;?>
        </div>
        <div id="divEditTelefonoOficina" style="display:none;">
          <b>Teléfono Oficina</b>:
          <input class="form-control" type="text" id="txtAsocTelOficina" name="txtAsocTelOficina" value="<?php print $strTelefonoOficina;?>">
        </div>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <div id="divShowRP1">
          <b>Ref. Personal 1</b>: <?php print $strRP1Nombre;?>
        </div>
        <div id="divEditRP1" style="display:none;">
          <b>Ref. Personal 1</b>:
          <input class="form-control" type="text" id="txtAsoRef1" name="txtAsoRef1" value="<?php print $strRP1Nombre;?>">
        </div>
      </td>
      <td colspan="2">
        <div id="divShowRP1Tel">
          <b>Teléfono</b>: <?php print $strRP1Telefono;?>
        </div>
        <div id="divEditRP1Tel" style="display:none;">
          <b>Teléfono</b>:
          <input class="form-control" type="text" id="txtAsocRp1Tel" name="txtAsocRp1Tel" value="<?php print $strRP1Telefono;?>">
        </div>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <div id="divShowRP2">
          <b>Ref. Personal 2</b>: <?php print $strRP2Nombre;?>
        </div>
        <div id="divEditRP2" style="display:none;">
          <b>Ref. Personal 2</b>:
          <input class="form-control" type="text" id="txtAsoRef2" name="txtAsoRef2" value="<?php print $strRP2Nombre;?>">
        </div>
      </td>
      <td colspan="2">
        <div id="divShowRP2Tel">
          <b>Teléfono</b>: <?php print $strRP2Telefono;?>
        </div>
        <div id="divEditRP2Tel" style="display:none;">
          <b>Teléfono</b>:
          <input class="form-control" type="text" id="txtAsocRp2Tel" name="txtAsocRp2Tel" value="<?php print $strRP2Telefono;?>">
        </div>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <div id="divShowRL1">
          <b>Ref. Laboral 1</b>: <?php print $strRL1Nombre;?>
        </div>
        <div id="divEditRL1" style="display:none;">
          <b>Ref. Laboral 1</b>:
          <input class="form-control" type="text" id="txtAsoRefLab1" name="txtAsoRefLab1" value="<?php print $strRL1Nombre;?>">
        </div>
      </td>
      <td colspan="2">
        <div id="divShowRL1Tel">
          <b>Teléfono</b>: <?php print $strRL1Telefono;?>
        </div>
        <div id="divEditRL1Tel" style="display:none;">
          <b>Teléfono</b>:
          <input class="form-control" type="text" id="txtAsocRL1Tel" name="txtAsocRl1Tel" value="<?php print $strRL1Telefono;?>">
        </div>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <div id="divShowRL2">
          <b>Ref. Laboral 2</b>: <?php print $strRL2Nombre;?>
        </div>
        <div id="divEditRL2" style="display:none;">
          <b>Ref. Laboral 2</b>:
          <input class="form-control" type="text" id="txtAsoRefLab2" name="txtAsoRefLab2" value="<?php print $strRL2Nombre;?>">
        </div>
      </td>
      <td colspan="2">
        <div id="divShowRL2Tel">
          <b>Teléfono</b>: <?php print $strRL2Telefono;?>
        </div>
        <div id="divEditRL2Tel" style="display:none;">
          <b>Teléfono</b>:
          <input class="form-control" type="text" id="txtAsocRL2Tel" name="txtAsocRl2Tel" value="<?php print $strRL2Telefono;?>">
        </div>
      </td>
    </tr>
    </table>
    <?php
  }

  public function drawBlurDetailContent($arrDetail){
    if( count($arrDetail)>0 ){
      $intPrestamo = $arrDetail["IDPRESTAMO"];
      $strNombreAgencia = $arrDetail["NOMBREAGENCIA"];
      $intNumeroPrestamo = $arrDetail["NUMERO"];
      $strEstadoPrestamo = $arrDetail["ESTADO_PRESTAMO"];
      $strFechaUltimoPago = $arrDetail["FECHA_ULTIMO_PAGO"];
      $strFechaPrimerDesembolso = $arrDetail["FECHA_PRIMER_DESEMBOLSO"];
      $strFechaProximoPago = $arrDetail["FECHA_PROXIMO_PAGO"];
      $decSaldoCapital = $arrDetail["SALDO_CAPITAL"];
      $decCapitalVencido = $arrDetail["CAPITAL_VENCIDO"];
      $decSaldoInteres = $arrDetail["SALDO_INTERES"];
      $decMontoMora = $arrDetail["MONTO_MORA"];
      $tmpdecCouta = $decCapitalVencido + $decSaldoInteres + $decMontoMora;

      $intDiasMoraCapital = $arrDetail["DIAS_MORA_CAPITAL"];
      $decRecargoCuotaAdmin = 0;
      if( $intDiasMoraCapital>60 ){
        $decRecargoCuotaAdmin = $tmpdecCouta * 0.10;
        $decCouta = $tmpdecCouta + $decRecargoCuotaAdmin;
      }else{
        $decCouta = $tmpdecCouta;
      }

      $strCuotaCero = $arrDetail["CUOTA_CERO"];
      $strGarantia = $arrDetail["GARANTIA"];

      $intConteoPromesas = getCountPromesasPago($intNumeroPrestamo);

      //Datos del asociado
      $intCIF = $arrDetail["CIF"];
      $strNombres = $arrDetail["NOMBRES"];
      $strCelular = $arrDetail["CELULAR"];
      $strTelefonoCasa = $arrDetail["TELEFONO_CASA"];
      $strTelefonoOficina = $arrDetail["TELEFONO_OFICINA"];

      $strRP1Nombre = $arrDetail["RP1_NOMBRE"];
      $strRP1Telefono = $arrDetail["RP1_TELFONO"];

      $strRP2Nombre = $arrDetail["RP2_NOMBRE"];
      $strRP2Telefono = $arrDetail["RP2_TELFONO"];

      $strRL1Nombre = $arrDetail["RL1_NOMBRE"];
      $strRL1Telefono = $arrDetail["RL1_TELEFONO"];

      $strRL2Nombre = $arrDetail["RL2_NOMBRE"];
      $strRL2Telefono = $arrDetail["RL2_TELEFONO"];

      
      ?>
      <table class="table table-borderless table-sm">
        <!-- datos del asociado-->
        <tr style="background-color: #dd4b39; color:white;">
          <td colspan="4">
            <h3>Datos del Asociado</h3>
            <input type="hidden" id="hdnPrestamoEvaluar" value="<?php print $intNumeroPrestamo; ?>">
          </td>
        </tr>
        <tr>
          <td colspan="4">
            <b>Nombre Completo</b>: <?php print ucwords($strNombres);?>
          </td>
        </tr>
        <tr>
          <td colspan="4">
            <div id="divRowsDataAsociado">
            <?php $this->drawRowsDatosAsociado($intCIF, $strCelular, $strTelefonoCasa, $strTelefonoOficina, $strRP1Nombre, $strRP1Telefono, $strRP2Nombre, $strRP2Telefono, $strRL1Nombre, $strRL1Telefono, $strRL2Nombre, $strRL2Telefono);?>
            </div>
          </td>
        </tr>
        <tr>
          <td colspan="2"><button class="btn btn-secondary btn-raised btn-block" onclick="editDataAsociado()">Editar datos del asociado</button></td>
          <td colspan="2"><button class="btn btn-secondary btn-raised btn-block" onclick="saveEditDatosAsoc()">Guardar Cambios</button></td>
        </tr>
        <tr>
          <td colspan="4"><br><br></td>
        </tr>
        <!-- datos del asociado-->
        <!-- datos del prestamo-->
        <tr>
          <td colspan="4" style="background-color: #dd4b39; color:white;"><h3>Datos del Préstamo</h3></td>
        </tr>
        <tr>
          <td colspan="2"><b>Agencia</b>: <?php print $strNombreAgencia;?></td>
          <td colspan="2"><b>Numero préstamo</b>: <?php print $intNumeroPrestamo;?></td>
        </tr>
        <tr>
          <td colspan="2"><b>Fecha primer desembolso</b>: <?php print $strFechaPrimerDesembolso; ?></td>
          <td colspan="2"><b>Fecha proximo pago</b>: <?php print $strFechaProximoPago; ?></td>
        </tr>
        <tr>
          <td colspan="2"><b>Estado préstamo</b>: <?php print $strEstadoPrestamo;?></td>
          <td colspan="2"><b>Fecha ultimo pago</b>: <?php print $strFechaUltimoPago; ?></td>
        </tr>
        <tr>
          <td colspan="2"><b>Dias Atraso Capital</b>: <?php print $intDiasMoraCapital;?></td>
          <td colspan="2"><b>Saldo Capital</b>: <?php print 'Q.'.number_format($decSaldoCapital, 2, '.', ',');?></td>
        </tr>
        <tr>
          <td colspan="2"><b>Gestiones registradas</b>: <?php print $intConteoPromesas;?></td>
          <td colspan="2">
          <b>Saldo Pendiente de Pago</b>: <?php print 'Q.'.number_format($decCouta, 2, '.', ',');?>
          <div style="text-align:center;">
          <br>
          - Capital Vencido: <?php print 'Q.'.number_format($decCapitalVencido, 2, '.', ',');?><br>
          - Saldo Interes: <?php print 'Q.'.number_format($decSaldoInteres, 2, '.', ',');?><br>
          - Monto Mora: <?php print 'Q.'.number_format($decMontoMora, 2, '.', ',');?><br>
          <?php
          if( $intDiasMoraCapital>60 ){
            ?>
            - Recargo por cobro administrativo: <?php print 'Q.'.number_format($decRecargoCuotaAdmin, 2, '.', ',');?>
            <?php
          }
          ?>
          </div>
          </td>
        </tr>
        <tr>
          <td colspan="2"><b>Cuota Cero</b>: <?php print $strCuotaCero;?></td>
          <td colspan="2"><b>Garantía</b>: <?php print $strGarantia;?></td>
        </tr>
        <!-- datos del prestamo-->
        <!-- Agregar promesa de pago-->
        <tr>
          <td colspan="3" style="background-color: #dd4b39; color:white;"><h3>Gestión de pago</h3></td>
        </tr>
        <tr>
          <td colspan="3"><b>Descripción</b>:</td>
        </tr>
        <tr>
          <td colspan="3">
            <textarea id="txtDescripcionPromesa" class="form-control"></textarea>
          </td>
        </tr>
        <tr>
          <td colspan="3"><b>Clasificación</b>:</td>
        </tr>
        <tr>
          <td colspan="3">
            <?php $this->drawSelectClasificacion(); ?>
          </td>
        </tr>
        <tr>
          <td colspan="3">
          <b>* Adjuntar imagen (tipo de images admitidas jpg, jpeg, gif, png, bmp)</b>:
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <input type="file" id="txtimagen" name="txtimagen" class="form-control">
          </td>
        </tr>
        <!-- Agregar promesa de pago-->
      </table>
      <br><br>
      <?php
    }
  }

  public function drawBlurPromesasdePago(){
    ?>
    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="modalContentPromesasPago">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header" style="background-color: #dd4b39; color:white; text-align:center;">
            <h1 class="modal-title">Bitácora gestión de pago</h1>
          </div>
          <div class="modal-body" id="divContentModalPromesasPago"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-raised" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
    <?php
  }

  public function drawBlurContentPromesas($arrListadoPromesas){
    if( count($arrListadoPromesas)>0 ){
      ?>
      <table class="table table-sm table-borderless table-striped">
        <thead>
          <tr>
            <th style="text-align:center;">No.</th>
            <th style="text-align:center;">Detalles</th>
            <th style="text-align:center;">Img. Adjunta</th>
            <th style="text-align:center;">Descripción</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $intCount = 0;
        reset($arrListadoPromesas);
        while( $rTMP = each($arrListadoPromesas) ){
          $intCount++;
          $intPromesa = $rTMP["key"];
          $strDescripcion = $rTMP["value"]["DESCRIPCION"];
          $strFecha = $rTMP["value"]["ADD_FECHA"];
          $strUsuario = $rTMP["value"]["USUARIO"];
          $strSubcategoria = utf8_encode($rTMP["value"]["SUBCATEGORIA"]);
          $strImagen = $rTMP["value"]["IMAGEN"];
          ?>
          <tr>
            <td style="text-align:justify;  vertical-align:middle;"><h5><span class="badge bg-red"><?php print $intCount;?></span></h5></td>
            <td style="text-align:center;">
            <?php 
            print '<b>Fecha:</b><br> '.$strFecha.'<br>';
            print '<b>Usuario:</b><br> '.$strUsuario.'<br>';
            print '<b>Categoría:</b><br> '.$strSubcategoria.'<br>';
            ?>
            </td>
            <td style="text-align:justify;  vertical-align:middle;">
            <?php 
              if( $strImagen!= '' ){
                ?>
                <img class="grow" src="images_gestion/<?php print $strImagen; ?>" width="50px" height="50px">
                <?php
              }else{
                print "No adjunto";
              }
            ?>
            </td>
            <td style="text-align:justify; vertical-align:middle;"><?php print $strDescripcion;?></td>
          </tr>
          <?php
        }
        ?>
        </tbody>
      </table>
      <?php
    }else{
      ?>
      <h3>No se han registrado gestiones de pago para este préstamo.</h3>
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

      <!-- DataTables CSS -->
      <link href="dist/css/jquery.dataTables.min.css" rel="stylesheet">
      

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

        .grow{
          transition: -moz-transform 0.1s ease-in 0s;
        }
        .grow:hover{
          transform: scale(8);
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
    <?php $this->drawBlurDetail(); ?>
    <?php $this->drawBlurPromesasdePago(); ?>
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
          <?php draMenu($this->arrRolUser,'listado_riesgo_alto', 1); ?>
        </section>
        <!-- /.sidebar -->
      </aside>

      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <br>
        <section class="content-header">
          <h1>Listado Riesgo Alto</h1>
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
                  <table class="table table-sm table-hover table-condensed table-striped" id="tblListadoRA">
                      <thead class="cf">
                          <tr style="background-color: #dd4b39; color:white;">
                              <th style="text-align:center;">No.</th>
                              <th style="text-align:center;">Agencia</th>
                              <th style="text-align:center;">Numero Prestamo</th>
                              <th style="text-align:center;">Nombres</th>
                              <th style="text-align:center;">Saldo Capital</th>
                              <th style="text-align:center;">Dias Atraso Capital</th>
                              <th style="text-align:center;">Gestiones Mes Actual</th>
                              <th style="text-align:center;">Cuota Cero</th>
                              <th style="text-align:center;">&nbsp;</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php
                          $arrListadoRA = $this->objModel->getListadoRiesgoAlto();
                          $intCount = 0;
                          if( count($arrListadoRA)>0 ){
                              reset($arrListadoRA);
                              while( $rTMP = each($arrListadoRA) ){
                                  $intCount++;
                                  $intId = $rTMP["key"];
                                  $intNumeroPrestamo = $rTMP["value"]["NUMERO"];
                                  $strAgencia = utf8_encode($rTMP["value"]["AGENCIA"]);
                                  $strNombres = utf8_encode($rTMP["value"]["NOMBRES"]);
                                  $decSaldoCapital = floatval($rTMP["value"]["SALDO_CAPITAL"]);
                                  $intDiasMoraCapital = intval($rTMP["value"]["DIAS_MORA_CAPITAL"]);
                                  $intConteoPromesas = intval($rTMP["value"]["PROMESAS"]);
                                  $decCapitalDesembolsado = floatval($rTMP["value"]["CAPITAL_DESEMBOLSADO"]);
                                  $strCuotaCero = ( $decSaldoCapital == $decCapitalDesembolsado )? "SI": "NO";
                                  ?>
                                  <tr id="trId_<?php print $intId;?>">
                                      <td data-title="No." style="text-align:center; vertical-align:middle;">
                                        <h5><span class="badge bg-red"><?php print $intCount;?></span></h5>
                                      </td>
                                      <td data-title="Agencia" style="text-align:center; vertical-align:middle;">
                                        <?php print $strAgencia;?>
                                      </td>
                                      <td data-title="Número de Prestamo" style="text-align:center; vertical-align:middle;">
                                        <?php print $intNumeroPrestamo;?>
                                      </td>
                                      <td data-title="Nombres" style="text-align:center; vertical-align:middle;">
                                        <?php print ucwords($strNombres);?>
                                      </td>
                                      <td data-title="Saldo Capital" style="text-align:center; vertical-align:middle;">
                                        <?php print 'Q.'.number_format($decSaldoCapital, 2, '.', ',');?>
                                      </td>
                                      <td data-title="Dias Atraso Capital" style="text-align:center; vertical-align:middle;">
                                          <?php print $intDiasMoraCapital;?>
                                      </td>
                                      <td data-title="Gestiones Registradas" style="text-align:center; vertical-align:middle;">
                                          <?php print $intConteoPromesas;?>
                                      </td>
                                      <td data-title="Cuota Cero" style="text-align:center; vertical-align:middle;">
                                          <?php print $strCuotaCero;?>
                                      </td>
                                      <td style="text-align:center;"><button class="btn btn-danger btn-raised" title="Ver detalles" onclick="getDetailPrestamo('<?php print $intId?>')">Ver detalles</button></td>
                                  </tr>
                                  <?php
                              }
                          }
                          ?>
                      </tbody>
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
                    url:"listado_riesgo_alto.php",
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

        function saveEditDatosAsoc(){

          var editCIF = $("#hdnCIF").val();
          var editAsocCelular = $("#txtAsocCelular").val();
          var editAsocTelCasa = $("#txtAsocTelCasa").val();
          var editAsocTelOfi = $("#txtAsocTelOficina").val();
          var editAsocRP1 = $("#txtAsoRef1").val();
          var editAsocRP1Tel = $("#txtAsocRp1Tel").val();
          var editAsocRP2 = $("#txtAsoRef2").val();
          var editAsocRP2Tel = $("#txtAsocRp2Tel").val();
          var editAsocRL1 = $("#txtAsoRefLab1").val();
          var editAsocRL1Tel = $("#txtAsocRL1Tel").val();
          var editAsocRL2 = $("#txtAsoRefLab2").val();
          var editAsocRL2Tel = $("#txtAsocRL2Tel").val();

          $.ajax({
              url:"listado_riesgo_alto.php",
              data:
              {
                saveEditDatosAsoc: true,
                editCIF: editCIF,
                editAsocCelular: editAsocCelular,
                editAsocTelCasa: editAsocTelCasa,
                editAsocTelOfi: editAsocTelOfi,
                editAsocRP1: editAsocRP1,
                editAsocRP1Tel: editAsocRP1Tel,
                editAsocRP2: editAsocRP2,
                editAsocRP2Tel: editAsocRP2Tel,
                editAsocRL1: editAsocRL1,
                editAsocRL1Tel: editAsocRL1Tel,
                editAsocRL2: editAsocRL2,
                editAsocRL2Tel: editAsocRL2Tel
              },
              type:"post",
              dataType: "html",
              success:function(data){
                $("#divRowsDataAsociado").html('');
                $("#divRowsDataAsociado").html(data);
              }
          });
        }

        function savePromesaPago(){
          var id = $("#hdnPrestamoEvaluar").val();
          var descripcion = $("#txtDescripcionPromesa").val();
          var categoria = $("#sltClasificacion").val();
          var boolError = false;

          if( $("#txtDescripcionPromesa").val() == '' ){
              $("#txtDescripcionPromesa").css('background-color','#f4d0de');
              boolError = true;
          }else{
              $("#txtDescripcionPromesa").css('background-color','');
          }

          var filename = $("#txtimagen").val(); 

          if( $("#txtimagen").val() != '' ){
            var extension = filename.replace(/^.*\./, '');
            var arrExt = ['jpg', 'jpeg', 'gif', 'png', 'bmp'];
            var isInArray = arrExt.includes(extension);
            if( isInArray == false ){
              boolError = true;
              alert("El tipo de archivo adjunto no es valido");
              $("#txtimagen").val(''); 
            }
          }
           

          if( boolError == false ){

            var inputFileImage = document.getElementById("txtimagen");
            var file = inputFileImage.files[0];
            var datos = new FormData();
            datos.append('txtimagen',file);
            datos.append('savePromesaPago',true);
            datos.append('prestamo',id);
            datos.append('categoria',categoria);
            datos.append('descripcion',descripcion);

            $.ajax({
              url:"listado_riesgo_alto.php",
              contentType: false,
              data: datos,
              processData: false,
              cache: false, 
              type:"post",
              dataType: "json",
              beforeSend: function() {
                $("#btnsavePromesaPago").prop('disabled', true);
              },
              success:function(data){
                if ( data.Correcto == "Y" ){
                  alert("La gestión se ha guardada correctamente");
                  $("#txtDescripcionPromesa").val("");
                  $("#txtimagen").val("");
                  $("#btnsavePromesaPago").prop('disabled', false);
                  location.href = "listado_riesgo_alto.php";
                }
              }
            });
          }
          
        }

        function openModalPromesasdePago(){
          var id = $("#hdnPrestamoEvaluar").val();
          if( id>0 ){
            $.ajax({
                url:"listado_riesgo_alto.php",
                data:
                {
                  getPromesasPago:true,
                  prestamo: id
                },
                type:"post",
                dataType: "html",
                success:function(data){
                   $('#modalContentPromesasPago').modal();
                   $("#divContentModalPromesasPago").html('');
                   $("#divContentModalPromesasPago").html(data);
                }
            });
          }
        }

        function getDetailPrestamo(id){
          if( id>0 ){
            $.ajax({
                url:"listado_riesgo_alto.php",
                data:
                {
                  getDetailPrestamo:true,
                  prestamo: id
                },
                type:"post",
                dataType: "html",
                success:function(data){
                   $('#modalContentDetail').modal({backdrop: 'static', keyboard: false});
                   $("#divContentModalDetail").html('');
                   $("#divContentModalDetail").html(data);
                }
            });
          }
        }

        function editDataAsociado(){
          $("#divEditCelular").show();
          $("#divShowCelular").hide();

          $("#divEditTelefonoCasa").show();
          $("#divShowTelefonoCasa").hide();

          $("#divEditTelefonoOficina").show();
          $("#divShowTelefonoOficina").hide();

          $("#divEditRP1").show();
          $("#divShowRP1").hide();

          $("#divEditRP1Tel").show();
          $("#divShowRP1Tel").hide();

          $("#divEditRP2").show();
          $("#divShowRP2").hide();

          $("#divEditRP2Tel").show();
          $("#divShowRP2Tel").hide();

          $("#divEditRL1").show();
          $("#divShowRL1").hide();

          $("#divEditRL1Tel").show();
          $("#divShowRL1Tel").hide();

          $("#divEditRL2").show();
          $("#divShowRL2").hide();

          $("#divEditRL2Tel").show();
          $("#divShowRL2Tel").hide();
        }

        $( document ).ready(function() {
          var idhash = $(location).attr('hash');
          if( idhash!='' ){
            $(idhash).css('backgroundColor', '#f9b5a7');
          }


          $('#tblListadoRA').DataTable({
            paging: false
          });
          
          $('#tblListadoRA_wrapper').find('label').each(function () {
              $(this).parent().append($(this).children());
          });
          $('#tblListadoRA_wrapper .dataTables_filter').find('input').each(function () {
              const $this = $(this);
              $this.removeClass('form-control-sm');
          });
          $('#tblListadoRA_wrapper .dataTables_length').addClass('d-flex flex-row');
          $('#tblListadoRA_wrapper .dataTables_filter').addClass('md-form');
          $('#tblListadoRA_wrapper select').removeClass('custom-select custom-select-sm form-control form-control-sm');
          $('#tblListadoRA_wrapper select').addClass('mdb-select');
          $('#tblListadoRA_wrapper .mdb-select').materialSelect();
          $('#tblListadoRA_wrapper .dataTables_filter').find('label').remove();
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
    <!-- DataTables JS -->
    <script src="dist/js/jquery.dataTables.min.js" type="text/javascript"></script>
    </body>
    </html>
    <?php
  }

}

?>



