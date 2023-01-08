<?php
require_once("core/core.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (isset($_SESSION['user_id'])) {
  $strRolUserSession = getRolUserSession($_SESSION['user_id']);
  $intIDUserSession = getIDUserSession($_SESSION['user_id']);
  $intAgenciaUserSession = getAgenciaByUsuario($intIDUserSession);

  if ($strRolUserSession != '') {
    $arrRolUser["ID"] = $intIDUserSession;
    $arrRolUser["NAME"] = $_SESSION['user_id'];
    $arrRolUser["AGENCIA"] = $intAgenciaUserSession;

    if ($strRolUserSession == "master") {
      $arrRolUser["MASTER"] = true;
    } elseif ($strRolUserSession == "normal") {
      $arrRolUser["NORMAL"] = true;
    } elseif ($strRolUserSession == "mora") {
      $arrRolUser["MORA"] = true;
    }
  }
} else {
  header("Location: index.php");
}

$objController = new dashboard_controller($arrRolUser);
$objController->runAjax();
$objController->drawContentController();

class dashboard_controller
{

  private $objModel;
  private $objView;
  private $arrRolUser;

  public function __construct($arrRolUser)
  {
    $this->objModel = new dashbaord_model($arrRolUser);
    $this->objView = new dashbaord_view($arrRolUser);
    $this->arrRolUser = $arrRolUser;
  }

  public function drawContentController()
  {
    $this->objView->drawContent();
  }

  public function runAjax()
  {
    $this->ajaxredirectListRiskHigh();
    $this->ajaxDestroySession();
    $this->ajaxDetailSaldoCapital();
    $this->ajaxDetailPrestamosPremora();
    $this->ajaxDetailPCG();
    $this->ajaxDetailPSG();
    $this->ajaxDetailAPCG();
    $this->ajaxDetailCC();
    $this->ajaxDetailIlocalizado();
    $this->ajaxDetailLocalizado();
    $this->ajaxDetailIrrecuperable();
    $this->ajaxDetailFromPremora();
    $this->ajaxSearchPrestamo();
    $this->ajaxRedirectListado();
  }

  public function ajaxDestroySession()
  {
    if (isset($_POST["destroSession"])) {
      header("Content-Type: application/json;");
      session_destroy();
      $arrReturn["Correcto"] = "Y";
      print json_encode($arrReturn);
      exit();
    }
  }

  public function ajaxredirectListRiskHigh()
  {
    if (isset($_POST["redirectListRiskHigh"])) {
      header("Content-Type: application/json;");
      $arrReturn["Correcto"] = "Y";
      print json_encode($arrReturn);
      exit();
    }
  }

  public function ajaxDetailSaldoCapital()
  {
    if (isset($_POST["getDetailSaldoCapital"]) && $_POST["getDetailSaldoCapital"] == "true") {
      header("Content-Type: application/html;");
      print $this->objView->drawDetailSaldoCapital();
      exit();
    }
  }

  public function ajaxDetailPrestamosPremora()
  {
    if (isset($_POST["getDetailPrestamoPremora"]) && $_POST["getDetailPrestamoPremora"] == "true") {
      header("Content-Type: application/html;");
      print $this->objView->drawDetailPrestamosPremora();
      exit();
    }
  }

  public function ajaxDetailPCG()
  {
    if (isset($_POST["getDetailPCG"]) && $_POST["getDetailPCG"] == "true") {
      header("Content-Type: application/html;");
      print $this->objView->drawDetailPCG();
      exit();
    }
  }

  public function ajaxDetailPSG()
  {
    if (isset($_POST["getDetailPSG"]) && $_POST["getDetailPSG"] == "true") {
      header("Content-Type: application/html;");
      print $this->objView->drawDetailPSG();
      exit();
    }
  }

  public function ajaxDetailAPCG()
  {
    if (isset($_POST["getDetailAPCG"]) && $_POST["getDetailAPCG"] == "true") {
      header("Content-Type: application/html;");
      $intAgencia = isset($_POST["intAgencia"]) ? intval($_POST["intAgencia"]) : 0;
      print $this->objView->drawDetailAPCG($intAgencia);
      exit();
    }
  }

  public function ajaxDetailCC()
  {
    if (isset($_POST["getDetailCC"]) && $_POST["getDetailCC"] == "true") {
      header("Content-Type: application/html;");
      print $this->objView->drawDetailCC();
      exit();
    }
  }

  public function ajaxDetailIlocalizado()
  {
    if (isset($_POST["getDetailIlocalizado"]) && $_POST["getDetailIlocalizado"] == "true") {
      header("Content-Type: application/html;");
      print $this->objView->drawDetailIlocalizado();
      exit();
    }
  }

  public function ajaxDetailLocalizado()
  {
    if (isset($_POST["getDetailLocalizado"]) && $_POST["getDetailLocalizado"] == "true") {
      header("Content-Type: application/html;");
      print $this->objView->drawDetailLocalizado();
      exit();
    }
  }

  public function ajaxDetailIrrecuperable()
  {
    if (isset($_POST["getDetailIrrecuperable"]) && $_POST["getDetailIrrecuperable"] == "true") {
      header("Content-Type: application/html;");
      print $this->objView->drawDetailIrrecuperable();
      exit();
    }
  }

  public function ajaxDetailFromPremora()
  {
    if (isset($_POST["getDetailFromPremora"]) && $_POST["getDetailFromPremora"] == "true") {
      header("Content-Type: application/html;");
      print $this->objView->drawDetailFromPremora();
      exit();
    }
  }

  public function ajaxSearchPrestamo()
  {
    if (isset($_POST["searchPremora"]) && $_POST["searchPremora"] == "true") {
      header("Content-Type: application/html;");
      $intPrestamo = isset($_POST["intPrestamo"]) ? intval($_POST["intPrestamo"]) : 0;
      $boolCorrecto = $this->objModel->boolNumeroPrestamoCorrecto($intPrestamo);
      $arrDetail = array();

      if ($boolCorrecto) {
        $arrDetail = $this->objModel->getDetallePrestamo($intPrestamo);
?>
        <script>
          $("#btnRedirectListado").show();
        </script>
      <?php
      } else {
      ?>
        <script>
          $("#btnRedirectListado").hide();
        </script>
    <?php
      }

      print $this->objView->drawBlurDetailContent($arrDetail);
      exit();
    }
  }

  public function ajaxRedirectListado()
  {
    if (isset($_POST["redirectListado"]) && $_POST["redirectListado"] == "true") {
      header("Content-Type: application/json;");
      $intPrestamo = isset($_POST["intPrestamo"]) ? intval($_POST["intPrestamo"]) : 0;
      $strTipoRiesgoPrestamo = $this->objModel->getTipoRiesgoPrestamo($intPrestamo);
      $intIDPrestamo = $this->objModel->getIdPrestamo($intPrestamo);
      $arrReturn["Tipo"] = $strTipoRiesgoPrestamo;
      $arrReturn["Idprestamo"] = $intIDPrestamo;
      print json_encode($arrReturn);
      exit();
    }
  }
}

class dashbaord_model
{

  private $arrRolUser;

  public function __construct($arrRolUser)
  {
    $this->arrRolUser = $arrRolUser;
  }

  function boolExisteUpdate($streditCIF)
  {
    if ($streditCIF != '') {
      $boolExisteUpdate = false;
      $conn = getConexion();
      $strQuery = "SELECT id FROM update_asociado WHERE cif = '{$streditCIF}'";
      $result = mysqli_query($conn, $strQuery);
      if (!empty($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
          $intId = $row["id"];
          $boolExisteUpdate = ($intId > 0) ? true : false;
        }
      }

      mysqli_close($conn);
      return $boolExisteUpdate;
    }
  }

  function getIdPrestamo($intPrestamo)
  {
    if ($intPrestamo > 0) {
      $conn = getConexion();
      $strQuery = "SELECT id FROM prestamo WHERE numero = {$intPrestamo}";
      $result = mysqli_query($conn, $strQuery);
      if (!empty($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
          $intId = $row["id"];;
        }
      }

      mysqli_close($conn);
      return $intId;
    }
  }

  function getTipoRiesgoPrestamo($intPrestamo)
  {
    if ($intPrestamo > 0) {
      $conn = getConexion();
      $strTipoRiesgoPrestamo = "";

      if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
        $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
      } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
        $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
      } else {
        $strAndDiasMora = "";
      }

      $strQuery = "SELECT CASE
                          WHEN prestamo.saldo_capital BETWEEN 0 and 50000 THEN 'Bajo'
                          WHEN prestamo.saldo_capital BETWEEN 50001 and 100000 THEN 'Medio'
                          ELSE 'Alto'
                          END AS tipo_riesgo
                     FROM prestamo 
                    WHERE prestamo.numero = {$intPrestamo}
                    {$strAndDiasMora}";
      $result = mysqli_query($conn, $strQuery);
      if (!empty($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
          $strTipoRiesgoPrestamo = $row["tipo_riesgo"];
        }
      }

      mysqli_close($conn);
      return $strTipoRiesgoPrestamo;
    }
  }

  function getDetallePrestamo($intPrestamo)
  {

    if ($intPrestamo > 0) {
      $conn = getConexion();
      $arrDetail = array();

      $strCIF = getCifByPrestamo($intPrestamo);
      $boolExisteUpdate = $this->boolExisteUpdate($strCIF);
      $strInnerJoinUA = ($boolExisteUpdate) ? "INNER JOIN update_asociado ON asociado.cif = update_asociado.cif" : "";

      if ($boolExisteUpdate) {
        $strCampoAsociado = "update_asociado.cif, 
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
      } else {
        $strCampoAsociado = "asociado.cif, 
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
                          prestamo.dias_mora_capital, 
                          prestamo.saldo_capital, 
                          {$strCampoAsociado}
                          prestamo.garantia
                     FROM prestamo 
                          INNER JOIN asociado 
                                  ON prestamo.asociado = asociado.id 
                          {$strInnerJoinUA}
                          INNER JOIN agencias
                                  ON prestamo.agenciacodigo = agencias.codigo
                    WHERE prestamo.numero = {$intPrestamo}";
      $result = mysqli_query($conn, $strQuery);
      if (!empty($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
          $arrDetail["IDPRESTAMO"] = $row["idprestamo"];
          $arrDetail["NOMBREAGENCIA"] = $row["nombreagencia"];
          $arrDetail["NUMERO"] = $row["numero"];
          $arrDetail["ESTADO_PRESTAMO"] = $row["estado_prestamo"];
          $arrDetail["DIAS_MORA_CAPITAL"] = $row["dias_mora_capital"];
          $arrDetail["NOMBRES"] = $row["nombres"];
        }
      }

      mysqli_close($conn);
      return $arrDetail;
    }
  }


  public function boolNumeroPrestamoCorrecto($intPrestamo)
  {
    if ($intPrestamo > 0) {
      $conn = getConexion();
      $boolCorrecto = false;
      $intID = 0;
      $strQuery = "SELECT id FROM prestamo WHERE numero = {$intPrestamo}";
      $result = mysqli_query($conn, $strQuery);
      if (!empty($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
          $intID = intval($row["id"]);
        }
      }

      $boolCorrecto = ($intID > 0) ? true : false;
      return $boolCorrecto;
    }
  }

  public function getConteoIlocalizado()
  {
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $intConteoIlocalizado = 0;
    $strQuery = "SELECT DISTINCT promesa_pago.prestamo,
                        COUNT(promesa_pago.id) conteo
                   FROM promesa_pago
                        INNER JOIN prestamo 
                                ON promesa_pago.prestamo = prestamo.numero
                        INNER JOIN subcategorias_gestiones 
                                ON promesa_pago.subcategoria_gestion = subcategorias_gestiones.id
                        INNER JOIN categorias_gestiones 
                                ON subcategorias_gestiones.categoria_gestion = categorias_gestiones.id
                  WHERE categorias_gestiones.id = 1
                  {$strAndDiasMora}
                  {$strAndAgenciaCodigo}";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $intConteoIlocalizado = intval($row["conteo"]);
      }
    }

    return $intConteoIlocalizado;
  }

  public function getDetalleIlocalizado()
  {
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $arrDetalle = array();
    $strQuery = "SELECT subcategorias_gestiones.nombre subcategoria, COUNT(promesa_pago.id) conteo
                   FROM promesa_pago
                        INNER JOIN prestamo 
                                ON promesa_pago.prestamo = prestamo.numero
                        INNER JOIN subcategorias_gestiones 
                                ON promesa_pago.subcategoria_gestion = subcategorias_gestiones.id
                        INNER JOIN categorias_gestiones 
                                ON subcategorias_gestiones.categoria_gestion = categorias_gestiones.id
                  WHERE categorias_gestiones.id = 1
                  {$strAndAgenciaCodigo}
                  {$strAndDiasMora}
                  GROUP BY subcategorias_gestiones.nombre
                  ORDER BY conteo DESC";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrDetalle[$row["subcategoria"]]["CONTEO"] = $row["conteo"];
      }
    }

    return $arrDetalle;
  }

  public function getConteoLocalizado()
  {
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $intConteoLocalizado = 0;
    $strQuery = "SELECT COUNT(promesa_pago.id) conteo
                   FROM promesa_pago
                        INNER JOIN prestamo 
                                ON promesa_pago.prestamo = prestamo.numero
                        INNER JOIN subcategorias_gestiones 
                                ON promesa_pago.subcategoria_gestion = subcategorias_gestiones.id
                        INNER JOIN categorias_gestiones 
                                ON subcategorias_gestiones.categoria_gestion = categorias_gestiones.id
                  WHERE categorias_gestiones.id = 2
                  {$strAndDiasMora}
                  {$strAndAgenciaCodigo}";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $intConteoLocalizado = intval($row["conteo"]);
      }
    }

    return $intConteoLocalizado;
  }

  public function getDetalleLocalizado()
  {
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $arrDetalle = array();
    $strQuery = "SELECT subcategorias_gestiones.nombre subcategoria, COUNT(promesa_pago.id) conteo
                   FROM promesa_pago
                        INNER JOIN prestamo 
                                ON promesa_pago.prestamo = prestamo.numero
                        INNER JOIN subcategorias_gestiones 
                                ON promesa_pago.subcategoria_gestion = subcategorias_gestiones.id
                        INNER JOIN categorias_gestiones 
                                ON subcategorias_gestiones.categoria_gestion = categorias_gestiones.id
                  WHERE categorias_gestiones.id = 2
                  {$strAndDiasMora}
                  {$strAndAgenciaCodigo}
                  GROUP BY subcategorias_gestiones.nombre
                  ORDER BY conteo DESC";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrDetalle[$row["subcategoria"]]["CONTEO"] = $row["conteo"];
      }
    }

    return $arrDetalle;
  }

  public function getConteoIrrecuperable()
  {
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $intConteoIrrecuperable = 0;
    $strQuery = "SELECT COUNT(promesa_pago.id) conteo
                   FROM promesa_pago
                        INNER JOIN prestamo 
                                ON promesa_pago.prestamo = prestamo.numero
                        INNER JOIN subcategorias_gestiones 
                                ON promesa_pago.subcategoria_gestion = subcategorias_gestiones.id
                        INNER JOIN categorias_gestiones 
                                ON subcategorias_gestiones.categoria_gestion = categorias_gestiones.id
                  WHERE categorias_gestiones.id = 3
                        {$strAndDiasMora}
                        {$strAndAgenciaCodigo}";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $intConteoIrrecuperable = intval($row["conteo"]);
      }
    }

    return $intConteoIrrecuperable;
  }

  public function getDetalleIrrecuperable()
  {
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $arrDetalle = array();
    $strQuery = "SELECT subcategorias_gestiones.nombre subcategoria, COUNT(promesa_pago.id) conteo
                   FROM promesa_pago
                        INNER JOIN prestamo 
                                ON promesa_pago.prestamo = prestamo.numero
                        INNER JOIN subcategorias_gestiones 
                                ON promesa_pago.subcategoria_gestion = subcategorias_gestiones.id
                        INNER JOIN categorias_gestiones 
                                ON subcategorias_gestiones.categoria_gestion = categorias_gestiones.id
                  WHERE categorias_gestiones.id = 3
                  {$strAndDiasMora}
                  {$strAndAgenciaCodigo}
                  GROUP BY subcategorias_gestiones.nombre
                  ORDER BY conteo DESC";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrDetalle[$row["subcategoria"]]["CONTEO"] = $row["conteo"];
      }
    }

    return $arrDetalle;
  }

  public function getTotalSaldoPremora()
  {
    $conn = getConexion();
    $intTotalSaldoPremora = 0;
    $intAgencia = $this->arrRolUser["AGENCIA"];
    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $strQuery = "SELECT SUM(saldo_capital) suma_saldo_premora
                   FROM prestamo 
                        INNER JOIN agencias 
                                ON prestamo.agenciacodigo = agencias.codigo
                  WHERE prestamo.saldo_capital>0
                  {$strAndDiasMora}
                  {$strAndAgenciaCodigo}";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $intTotalSaldoPremora = intval($row["suma_saldo_premora"]);
      }
    }

    return $intTotalSaldoPremora;
  }

  public function getDetalleSaldoCapital()
  {
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];
    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $arrDetalleSC = array();
    $strQuery = "SELECT agencias.nombre nombre_agencia, 
                        SUM(prestamo.saldo_capital) saldo_capital
                   FROM prestamo
                        INNER JOIN agencias 
                                ON prestamo.agenciacodigo = agencias.codigo
                  WHERE agencias.nombre IS NOT NULL
                  {$strAndDiasMora}
                  {$strAndAgenciaCodigo}
                  GROUP BY agencias.nombre
                  ORDER BY saldo_capital DESC";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrDetalleSC[$row["nombre_agencia"]]["SALDO_CAPITAL"] = $row["saldo_capital"];
      }
    }

    return $arrDetalleSC;
  }

  public function getCantidadPrestamosPremora()
  {
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }


    $intCantidadPrestamosPremora = 0;
    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $strQuery = "SELECT COUNT(id) cantidad_prestamos_premora 
                   FROM prestamo 
                  WHERE id IS NOT NULL
                  {$strAndDiasMora}
                  {$strAndAgenciaCodigo}";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $intCantidadPrestamosPremora = intval($row["cantidad_prestamos_premora"]);
      }
    }

    return $intCantidadPrestamosPremora;
  }

  public function getDetallePrestamoPremora()
  {
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $arrDetallePP = array();
    $strQuery = "SELECT agencias.nombre nombre_agencia, 
                        COUNT(prestamo.id) conteo
                   FROM prestamo
                        INNER JOIN agencias 
                                ON prestamo.agenciacodigo = agencias.codigo
                  WHERE prestamo.id IS NOT NULL
                        {$strAndDiasMora}
                        {$strAndAgenciaCodigo}
                  GROUP BY agencias.nombre
                  ORDER BY conteo DESC";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrDetallePP[$row["nombre_agencia"]]["CONTEO"] = $row["conteo"];
      }
    }

    return $arrDetallePP;
  }

  public function getCountPrestamosConGestion()
  {
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $intCountPrestamosConGestion = 0;
    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $strQuery = "SELECT COUNT(prestamo.id) conteo_con_gestion
                   FROM promesa_pago
                        INNER JOIN prestamo 
                                ON promesa_pago.prestamo = prestamo.numero
                        INNER JOIN agencias 
                                ON prestamo.agenciacodigo = agencias.codigo 
                  WHERE prestamo.id IS NOT NULL
                  {$strAndDiasMora}
                  {$strAndAgenciaCodigo}";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $intCountPrestamosConGestion = intval($row["conteo_con_gestion"]);
      }
    }

    return $intCountPrestamosConGestion;
  }

  public function getCountDetailPrestamosConGestion()
  {
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $arrDetallePCG = array();
    $strQuery = "SELECT agencias.codigo idagencia,
                        agencias.nombre nombre_agencia, 
                        COUNT(promesa_pago.id) conteo_con_gestion,
                        SUM(prestamo.saldo_capital) saldo_con_gestion
                   FROM promesa_pago
                        INNER JOIN prestamo 
                                ON promesa_pago.prestamo = prestamo.numero
                        INNER JOIN agencias 
                                ON prestamo.agenciacodigo = agencias.codigo 
                  WHERE prestamo.saldo_capital>0
                  {$strAndDiasMora}
                  {$strAndAgenciaCodigo}
                  GROUP BY agencias.nombre
                  ORDER BY conteo_con_gestion DESC";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrDetallePCG[$row["nombre_agencia"]]["AGENCIA"] = $row["idagencia"];
        $arrDetallePCG[$row["nombre_agencia"]]["CONTEO"] = $row["conteo_con_gestion"];
        $arrDetallePCG[$row["nombre_agencia"]]["SALDO_CON_GESTION"] = $row["saldo_con_gestion"];
      }
    }

    return $arrDetallePCG;
  }

  public function getCountPrestamosCuotaCero()
  {
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $intCountCuotaCero = 0;
    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $strQuery = "SELECT COUNT(id) conteo_cuota_cero
                   FROM prestamo
                  WHERE capital_desembolsado = saldo_capital
                        {$strAndAgenciaCodigo}
                        {$strAndDiasMora}";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $intCountCuotaCero = intval($row["conteo_cuota_cero"]);
      }
    }

    return $intCountCuotaCero;
  }

  public function getCountDetailCuotaCero()
  {
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }


    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $arrDetalleCuotaCero = array();
    $strQuery = "SELECT agencias.nombre nombre_agencia,
                        COUNT(prestamo.id) conteo_cuota_cero,
                        SUM(prestamo.saldo_capital) saldo_sin_gestion
                   FROM prestamo
                        INNER JOIN agencias 
                                ON prestamo.agenciacodigo = agencias.codigo
                  WHERE capital_desembolsado = saldo_capital
                    {$strAndDiasMora}
                    {$strAndAgenciaCodigo}
               GROUP BY agencias.nombre
               ORDER BY saldo_sin_gestion DESC";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrDetalleCuotaCero[$row["nombre_agencia"]]["CONTEO"] = $row["conteo_cuota_cero"];
        $arrDetalleCuotaCero[$row["nombre_agencia"]]["SALDO_SIN_GESTION"] = $row["saldo_sin_gestion"];
      }
    }

    return $arrDetalleCuotaCero;
  }

  public function getCountPrestamosSinGestion()
  {
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];
    $intCountPrestamosSinGestion = 0;

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $strQuery = "SELECT COUNT(id) conteo_sin_gestion
                   FROM prestamo
                  WHERE prestamo.numero NOT IN ( SELECT prestamo FROM promesa_pago )
                        {$strAndAgenciaCodigo}
                        {$strAndDiasMora}";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $intCountPrestamosSinGestion = intval($row["conteo_sin_gestion"]);
      }
    }

    return $intCountPrestamosSinGestion;
  }

  public function getCountDetailPrestamosSinGestion()
  {
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $arrDetallePSG = array();
    $strQuery = "SELECT agencias.nombre nombre_agencia,
                        COUNT(prestamo.id) conteo_sin_gestion,
                        SUM(prestamo.saldo_capital) saldo_sin_gestion
                   FROM prestamo
                        INNER JOIN agencias 
                                ON prestamo.agenciacodigo = agencias.codigo
                  WHERE prestamo.numero NOT IN (SELECT prestamo FROM promesa_pago )
                    {$strAndDiasMora}
                    {$strAndAgenciaCodigo}
               GROUP BY agencias.nombre
               ORDER BY saldo_sin_gestion DESC";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrDetallePSG[$row["nombre_agencia"]]["CONTEO"] = $row["conteo_sin_gestion"];
        $arrDetallePSG[$row["nombre_agencia"]]["SALDO_SIN_GESTION"] = $row["saldo_sin_gestion"];
      }
    }

    return $arrDetallePSG;
  }

  public function getDetalleAPCG($intAgencia)
  {
    $conn = getConexion();

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $arrDetalleAPCG = array();
    $strQuery = "SELECT usuarios.nombre usuario, COUNT(promesa_pago.id) conteo
                   FROM promesa_pago
                        INNER JOIN prestamo 
                                ON promesa_pago.prestamo = prestamo.numero
                        INNER JOIN agencias 
                                ON prestamo.agenciacodigo = agencias.codigo
                        INNER JOIN usuarios 
                                ON promesa_pago.add_user = usuarios.id
                  WHERE promesa_pago.id IS NOT NULL
                        {$strAndDiasMora}
                    AND agencias.codigo IN ({$intAgencia})
                  GROUP BY usuarios.nombre";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrDetalleAPCG[$row["usuario"]]["CONTEO"] = $row["conteo"];
      }
    }

    return $arrDetalleAPCG;
  }

  public function getLogSaldoPremora()
  {
    $conn = getConexion();
    $arrLogSaldoPremora = array();
    $strQuery = "SELECT DATE_FORMAT(fecha, '%d/%m/%Y') fechamostrada,
                        saldo 
                   FROM log_saldo_premora 
                  ORDER BY fecha DESC
                  LIMIT 10";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrLogSaldoPremora[$row["fechamostrada"]]["SALDO"] = $row["saldo"];
      }
    }

    return $arrLogSaldoPremora;
  }

  public function getConteoGestionesDate()
  {
    $conn = getConexion();
    $arrConteoGestiones = array();
    $intAgencia = $this->arrRolUser["AGENCIA"];
    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $strQuery = "SELECT DATE_FORMAT(promesa_pago.add_fecha, '%d/%m/%Y') fecha, 
                        COUNT(promesa_pago.id) conteo,
                        DATE_FORMAT(promesa_pago.add_fecha, '%m/%d') fecha2
                   FROM promesa_pago
                        LEFT JOIN prestamo 
                               ON promesa_pago.prestamo = prestamo.numero
                        LEFT JOIN agencias 
                               ON prestamo.agenciacodigo = agencias.codigo 
                  WHERE promesa_pago.id IS NOT NULL
                  {$strAndAgenciaCodigo}
                  GROUP BY fecha2
                  ORDER BY fecha2 DESC
                  LIMIT 15";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrConteoGestiones[$row["fecha"]]["CONTEO"] = $row["conteo"];
      }
    }

    return $arrConteoGestiones;
  }

  public function getConteoFromPremora()
  {
    $conn = getConexion();
    $arrConteoFromPremora = array();
    $intAgencia = $this->arrRolUser["AGENCIA"];
    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $strQuery = "SELECT agencias.nombre agencia,
                        COUNT(prestamo.id) conteo 
                   FROM prestamo 
                        INNER JOIN historial_premora 
                                ON historial_premora.numero_prestamo = prestamo.numero
                        INNER JOIN agencias 
                                ON prestamo.agenciacodigo = agencias.codigo
                  WHERE prestamo.dias_mora_capital>30
                    AND DATE_FORMAT(historial_premora.cierre, '%m-%Y') = DATE_FORMAT(date_sub(NOW(), INTERVAL 1 MONTH),'%m-%Y')
                    {$strAndAgenciaCodigo}
                  GROUP BY agencia
                  ORDER BY conteo DESC";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrConteoFromPremora[$row["agencia"]]["CONTEO"] = $row["conteo"];
      }
    }

    return $arrConteoFromPremora;
  }


  public function getEstadisticas()
  {

    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";
    $arrEstadisticas = array();
    $arrEstadisticas["RIESGO_BAJO_CONTEO"] = 0;
    $arrEstadisticas["RIESGO_BAJO_COLOR"] = "";

    $arrEstadisticas["RIESGO_MEDIO_CONTEO"] = 0;
    $arrEstadisticas["RIESGO_MEDIO_COLOR"] = "";

    $arrEstadisticas["RIESGO_ALTO_CONTEO"] = 0;
    $arrEstadisticas["RIESGO_ALTO_COLOR"] = "";

    //RIESGO BAJO
    $strQueryRB = "SELECT COUNT(id) conteo_riesgo_bajo 
                     FROM prestamo 
                    WHERE saldo_capital BETWEEN  (SELECT rango_inicial FROM estado_premora WHERE id = 1) AND (SELECT rango_final FROM estado_premora WHERE id = 1)
                      {$strAndDiasMora}
                      {$strAndAgenciaCodigo}";
    $result = mysqli_query($conn, $strQueryRB);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrEstadisticas["RIESGO_BAJO_CONTEO"] = intval($row["conteo_riesgo_bajo"]);
      }
    }

    $strQueryRBC = "SELECT color
                      FROM estado_premora 
                     WHERE id = 1";
    $result = mysqli_query($conn, $strQueryRBC);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrEstadisticas["RIESGO_BAJO_COLOR"] = $row["color"];
      }
    }

    //RIESGO MEDIO
    $strQueryRM = "SELECT COUNT(id) conteo_riesgo_medio 
                     FROM prestamo 
                    WHERE saldo_capital BETWEEN  (SELECT rango_inicial FROM estado_premora WHERE id = 2) AND (SELECT rango_final FROM estado_premora WHERE id = 2)
                      {$strAndDiasMora}
                      {$strAndAgenciaCodigo}";
    $result = mysqli_query($conn, $strQueryRM);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrEstadisticas["RIESGO_MEDIO_CONTEO"] = intval($row["conteo_riesgo_medio"]);
      }
    }

    $strQueryRMC = "SELECT color
                      FROM estado_premora 
                     WHERE id = 2";
    $result = mysqli_query($conn, $strQueryRMC);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrEstadisticas["RIESGO_MEDIO_COLOR"] = $row["color"];
      }
    }

    //RIESGO ALTO
    $strQueryRA = "SELECT COUNT(id) conteo_riesgo_alto 
                     FROM prestamo 
                    WHERE saldo_capital BETWEEN  (SELECT rango_inicial FROM estado_premora WHERE id = 3) AND (SELECT rango_final FROM estado_premora WHERE id = 3)
                      {$strAndDiasMora}
                      {$strAndAgenciaCodigo}";
    $result = mysqli_query($conn, $strQueryRA);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrEstadisticas["RIESGO_ALTO_CONTEO"] = intval($row["conteo_riesgo_alto"]);
      }
    }

    $strQueryRAC = "SELECT color
                      FROM estado_premora 
                     WHERE id = 3";
    $result = mysqli_query($conn, $strQueryRAC);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrEstadisticas["RIESGO_ALTO_COLOR"] = $row["color"];
      }
    }

    $strQueryFromHp = "SELECT COUNT(prestamo.id) conteo_from_premora
                         FROM prestamo 
                              INNER JOIN historial_premora 
                                      ON historial_premora.numero_prestamo = prestamo.numero
                        WHERE prestamo.dias_mora_capital>30
                          AND DATE_FORMAT(historial_premora.cierre, '%m-%Y') = DATE_FORMAT(date_sub(NOW(), INTERVAL 1 MONTH),'%m-%Y')";
    $result = mysqli_query($conn, $strQueryFromHp);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrEstadisticas["CONTEO_FROM_PREMORA"] = $row["conteo_from_premora"];
      }
    }

    mysqli_close($conn);
    return $arrEstadisticas;
  }

  function getListadoRiesgoAlto()
  {
    $conn = getConexion();
    $intAgencia = $this->arrRolUser["AGENCIA"];
    $strAndAgenciaCodigo = (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) ? "AND prestamo.agenciacodigo IN ({$intAgencia})" : "";

    if (isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital BETWEEN 1 AND 30";
    } elseif (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
      $strAndDiasMora = "AND prestamo.dias_mora_capital>30 ";
    } else {
      $strAndDiasMora = "";
    }

    $arrListadoRA = array();
    $strQuery = "SELECT prestamo.id, 
                        agencias.nombre nombreagencia,
                        prestamo.numero, 
                        asociado.nombres,
                        prestamo.saldo_capital,
                        prestamo.dias_mora_capital,
                        (SELECT COUNT(id) conteo 
                           FROM promesa_pago 
                          WHERE prestamo = prestamo.numero 
                            AND DATE_FORMAT(add_fecha, '%m-%Y') = DATE_FORMAT(NOW(), '%m-%Y') ) promesas
                   FROM prestamo 
                        INNER JOIN asociado 
                                ON prestamo.asociado = asociado.id 
                        INNER JOIN agencias
                                ON prestamo.agenciacodigo = agencias.codigo
                  WHERE prestamo.saldo_capital BETWEEN (SELECT rango_inicial FROM estado_premora WHERE id = 3) AND (SELECT rango_final FROM estado_premora WHERE id = 3) 
                    {$strAndDiasMora} 
                    {$strAndAgenciaCodigo}
                  ORDER BY promesas DESC LIMIT 25";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrListadoRA[$row["id"]]["NUMERO"] = $row["numero"];
        $arrListadoRA[$row["id"]]["AGENCIA"] = $row["nombreagencia"];
        $arrListadoRA[$row["id"]]["NOMBRES"] = $row["nombres"];
        $arrListadoRA[$row["id"]]["SALDO_CAPITAL"] = $row["saldo_capital"];
        $arrListadoRA[$row["id"]]["DIAS_MORA_CAPITAL"] = $row["dias_mora_capital"];
        $arrListadoRA[$row["id"]]["PROMESAS"] = $row["promesas"];
      }
    }

    mysqli_close($conn);
    return $arrListadoRA;
  }
}

class dashbaord_view
{

  private $objModel;
  private $arrRolUser;

  public function __construct($arrRolUser)
  {
    $this->objModel = new dashbaord_model($arrRolUser);
    $this->arrRolUser = $arrRolUser;
  }

  public function drawBlurDetailFromPremora()
  {
    ?>
    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="modalContentDetailDesdePremora">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header" style="background-color: #00c0ef; color:white; text-align:center;">
            <h1 class="modal-title">Detalle Conteo desde Premora</h1>
          </div>
          <div class="modal-body" id="divContentModalDetailDesdePremora">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-raised" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  <?php
  }

  public function drawBlurDetailSaldoCapital()
  {
  ?>
    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="modalContentDetailSaldoCapital">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header" style="background-color: #00c0ef; color:white; text-align:center;">
            <h1 class="modal-title">Detalle del Saldo Premora por Agencia</h1>
          </div>
          <div class="modal-body" id="divContentModalDetailSaldoCapital">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-raised" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  <?php
  }

  public function drawBlurDetailPrestamoPremora()
  {
  ?>
    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="modalContentDetailPrestamoPremora">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header" style="background-color: #00c0ef; color:white; text-align:center;">
            <h1 class="modal-title">Detalle Cantidad Préstamos Premora por Agencia</h1>
          </div>
          <div class="modal-body" id="divContentModalDetailPrestamoPremora">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-raised" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  <?php
  }

  public function drawBlurDetailPCG()
  {
  ?>
    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="modalContentDetailPCG">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header" style="background-color: #00c0ef; color:white; text-align:center;">
            <h1 class="modal-title">Detalle Cantidad Préstamos con Gestión por Agencia</h1>
          </div>
          <div class="modal-body" id="divContentModalDetailPCG">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-raised" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  <?php
  }

  public function drawBlurDetailPSG()
  {
  ?>
    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="modalContentDetailPSG">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header" style="background-color: #00c0ef; color:white; text-align:center;">
            <h1 class="modal-title">Detalle por Agencia Cantidad Préstamos sin Gestión</h1>
          </div>
          <div class="modal-body" id="divContentModalDetailPSG">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-raised" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  <?php
  }

  public function drawBlurDetailAPCG()
  {
  ?>
    <div class="modal" tabindex="-1" role="dialog" id="modalContentDetailAPCG">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header" style="background-color: #00c0ef; color:white; text-align:center;">
            <h1 class="modal-title">Detalle por Usuario Cantidad Préstamos con Gestión</h1>
          </div>
          <div class="modal-body" id="divContentModalDetailAPCG">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-raised" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  <?php
  }

  public function drawBlurDetailCuotaCero()
  {
  ?>
    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="modalContentDetailCC">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header" style="background-color: #00c0ef; color:white; text-align:center;">
            <h1 class="modal-title">Detalle por Agencia Cuota Cero</h1>
          </div>
          <div class="modal-body" id="divContentModalDetailCC">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-raised" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  <?php
  }

  public function drawBlurDetailIlocalizado()
  {
  ?>
    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="modalContentDetailIlocalizado">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header" style="background-color: #00a65a; color:white; text-align:center;">
            <h1 class="modal-title">Detalle Recuento Ilocalizado</h1>
          </div>
          <div class="modal-body" id="divContentModalDetailIlocalizado">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-raised" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  <?php
  }

  public function drawBlurDetailLocalizado()
  {
  ?>
    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="modalContentDetailLocalizado">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header" style="background-color: #00a65a; color:white; text-align:center;">
            <h1 class="modal-title">Detalle Recuento Localizado</h1>
          </div>
          <div class="modal-body" id="divContentModalDetailLocalizado">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-raised" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  <?php
  }

  public function drawBlurDetailIrrecuperable()
  {
  ?>
    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="modalContentDetailIrrecuperable">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header" style="background-color: #00a65a; color:white; text-align:center;">
            <h1 class="modal-title">Detalle Recuento Irrecuperable</h1>
          </div>
          <div class="modal-body" id="divContentModalDetailIrrecuperable">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-raised" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  <?php
  }

  public function drawBlurDetalleBusqueda()
  {
  ?>
    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="modalBlurDetalleBusqueda">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header" style="background-color: #2196f3; color:white; text-align:center;">
            <h1 class="modal-title">Detalle de la busqueda</h1>
          </div>
          <div class="modal-body" id="divContentModalBlurDetalleBusqueda">
          </div>
          <div class="modal-footer">
            <button type="button" id="btnRedirectListado" class="btn btn-info btn-raised" onclick="redirectListado()">
              <i class="fa fa-search" aria-hidden="true"></i> Verificar
            </button>
            <button type="button" class="btn btn-secondary btn-raised" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  <?php
  }

  public function drawDetailSaldoCapital()
  {
    $arrDetalleSC = $this->objModel->getDetalleSaldoCapital();

    $arrDetalleSCTMP = $arrDetalleSC;
    $decTotalSaldoCapitalTMP = 0;
    foreach( $arrDetalleSCTMP as $key => $val ){
      $decSaldoCapitalTMP = $val["SALDO_CAPITAL"];
      $decTotalSaldoCapitalTMP = $decTotalSaldoCapitalTMP + $decSaldoCapitalTMP;
    }


    $decTotalSaldoCapital = 0;
  ?>
    <table class="table table-sm table-hover table-borderless">
      <thead>
        <tr>
          <th style="text-align: center;">No. </th>
          <th style="text-align: center;">Agencia</th>
          <th style="text-align: center;">Porcentaje</th>
          <th style="text-align: center;">Saldo</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $intCount = 0;
        foreach( $arrDetalleSC as $key => $val ){
          $intCount++;
          $strAgencia = $key;
          $decSaldoCapital = $val["SALDO_CAPITAL"];
          $decPorcentaje = ($decSaldoCapital / $decTotalSaldoCapitalTMP) * 100;
          $decTotalSaldoCapital = $decTotalSaldoCapital + $decSaldoCapital;
        ?>
          <tr>
            <td style="text-align: center;">
              <h5><span class="badge bg-light-blue"><?php print $intCount; ?></span></h5>
            </td>
            <td style="text-align: center;"><?php print $strAgencia; ?></td>
            <td style="text-align: center;"><?php print number_format($decPorcentaje, 2) . '%'; ?></td>
            <td style="text-align: center;"><?php print 'Q.' . number_format($decSaldoCapital, 2, '.', ','); ?></td>
          </tr>
        <?php
        }
        ?>
        <tr>
          <td>&nbsp;</td>
          <td style="text-align: right;"><b>Total..</b></td>
          <td style="text-align: center;"><b>100%<b></td>
          <td style="text-align: center;"><b><?php print 'Q.' . number_format($decTotalSaldoCapital, 2, '.', ','); ?></b></td>
        </tr>
      </tbody>
    </table>
  <?php
  }

  public function drawDetailFromPremora()
  {
    $arrConteoFromPremora = $this->objModel->getConteoFromPremora();

    $arrDetalleFP = $arrConteoFromPremora;
    $decSUMAConteoTMP = 0;
    foreach( $arrDetalleFP as $key => $val ){
      $intConteoTMP = $val["CONTEO"];
      $decSUMAConteoTMP = $decSUMAConteoTMP + $intConteoTMP;
    }

    $intCantidadFP = 0;
  ?>
    <table class="table table-sm table-hover table-borderless">
      <thead>
        <tr>
          <th style="text-align: center;">No. </th>
          <th style="text-align: center;">Agencia</th>
          <th style="text-align: center;">Porcentaje</th>
          <th style="text-align: center;">Cantidad</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $intCount = 0;
        foreach( $arrConteoFromPremora as $key => $val ){
          $intCount++;
          $strAgencia = $key;
          $intCantidad = $val["CONTEO"];
          $decPorcentaje = ($intCantidad / $decSUMAConteoTMP) * 100;
          $intCantidadFP = $intCantidadFP + $intCantidad;
        ?>
          <tr>
            <td style="text-align: center;">
              <h5><span class="badge bg-light-blue"><?php print $intCount; ?></span></h5>
            </td>
            <td style="text-align: center;"><?php print $strAgencia; ?></td>
            <td style="text-align: center;"><?php print number_format($decPorcentaje, 2) . '%'; ?></td>
            <td style="text-align: center;"><?php print $intCantidad; ?></td>
          </tr>
        <?php
        }
        ?>
        <tr>
          <td>&nbsp;</td>
          <td style="text-align: right;"><b>Total..</b></td>
          <td style="text-align: center;"><b>100%</b></td>
          <td style="text-align: center;"><b><?php print $intCantidadFP; ?></b></td>
        </tr>
      </tbody>
    </table>
  <?php
  }

  public function drawDetailPrestamosPremora()
  {
    $arrDetallePP = $this->objModel->getDetallePrestamoPremora();

    $arrDetallePPTMP = $arrDetallePP;
    $decSUMAConteoTMP = 0;
    foreach( $arrDetallePPTMP as $key => $val ){
      $intConteoTMP = $val["CONTEO"];
      $decSUMAConteoTMP = $decSUMAConteoTMP + $intConteoTMP;
    }

    $intCantidadPP = 0;
  ?>
    <table class="table table-sm table-hover table-borderless">
      <thead>
        <tr>
          <th style="text-align: center;">No. </th>
          <th style="text-align: center;">Agencia</th>
          <th style="text-align: center;">Porcentaje</th>
          <th style="text-align: center;">Cantidad</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $intCount = 0;
        foreach( $arrDetallePP as $key => $val ){
          $intCount++;
          $strAgencia = $key;
          $intCantidad = $val["CONTEO"];
          $decPorcentaje = ($intCantidad / $decSUMAConteoTMP) * 100;
          $intCantidadPP = $intCantidadPP + $intCantidad;
        ?>
          <tr>
            <td style="text-align: center;">
              <h5><span class="badge bg-light-blue"><?php print $intCount; ?></span></h5>
            </td>
            <td style="text-align: center;"><?php print $strAgencia; ?></td>
            <td style="text-align: center;"><?php print number_format($decPorcentaje, 2) . '%'; ?></td>
            <td style="text-align: center;"><?php print $intCantidad; ?></td>
          </tr>
        <?php
        }
        ?>
        <tr>
          <td>&nbsp;</td>
          <td style="text-align: right;"><b>Total..</b></td>
          <td style="text-align: center;"><b>100%</b></td>
          <td style="text-align: center;"><b><?php print $intCantidadPP; ?></b></td>
        </tr>
      </tbody>
    </table>
  <?php
  }

  public function drawDetailPCG()
  {
    $arrDetallePCG = $this->objModel->getCountDetailPrestamosConGestion();

    $arrDetallePCGTMP = $arrDetallePCG;
    $decSUMAConteoTMP = 0;
    foreach( $arrDetallePCGTMP as $key => $val ){
      $intConteoTMP = $val["CONTEO"];
      $decSUMAConteoTMP = $decSUMAConteoTMP + $intConteoTMP;
    }

    $intCantidadPCG = 0;
    $decSaldoPCG = 0;
  ?>
    <table class="table table-sm table-hover table-borderless">
      <thead>
        <tr>
          <th style="text-align: center;">No. </th>
          <th style="text-align: center;">Agencia</th>
          <th style="text-align: center;">Porcentaje</th>
          <th style="text-align: center;">Cantidad</th>
          <th style="text-align: center;">Saldo con gestión</th>
          <th style="text-align: center;">&nbsp;</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $intCount = 0;
        foreach( $arrDetallePCG as $key => $val ){
          $intCount++;
          $strAgencia = $key;
          $intAgencia = intval($val["AGENCIA"]);
          $intCantidad = $val["CONTEO"];
          $decPorcentaje = ($intCantidad / $decSUMAConteoTMP) * 100;
          $intCantidadPCG = $intCantidadPCG + $intCantidad;
          $decSaldoGestion = $val["SALDO_CON_GESTION"];
          $decSaldoPCG = $decSaldoPCG + $decSaldoGestion;
        ?>
          <tr>
            <td style="text-align: center; vertical-align:middle;">
              <h5><span class="badge bg-light-blue"><?php print $intCount; ?></span></h5>
            </td>
            <td style="text-align: center; vertical-align:middle;"><?php print $strAgencia; ?></td>
            <td style="text-align: center; vertical-align:middle;"><?php print number_format($decPorcentaje, 2) . '%'; ?></td>
            <td style="text-align: center; vertical-align:middle;"><?php print $intCantidad; ?></td>
            <td style="text-align: center; vertical-align:middle;"><?php print 'Q.' . number_format($decSaldoGestion, 2, '.', ','); ?></td>
            <?php
            if (isset($this->arrRolUser["MASTER"]) && $this->arrRolUser["MASTER"] == true) {
            ?>
              <td style="text-align: center; vertical-align:middle;">
                <button class="btn btn-info btn-sm btn-raised" onclick="openModalDetailAPCG('<?php print $intAgencia; ?>')">
                  <i class="fa fa-search" aria-hidden="true"></i> Ver detalles
                </button>
              </td>
            <?php
            }
            ?>
          </tr>
        <?php
        }
        ?>
        <tr>
          <td>&nbsp;</td>
          <td style="text-align: right; vertical-align:middle;"><b>Total..</b></td>
          <td style="text-align: center;"><b>100%</b></td>
          <td style="text-align: center; vertical-align:middle;"><b><?php print $intCantidadPCG; ?></b></td>
          <td style="text-align: center; vertical-align:middle;"><b><?php print 'Q.' . number_format($decSaldoPCG, 2, '.', ','); ?></b></td>
        </tr>
      </tbody>
    </table>
  <?php
  }

  public function drawDetailPSG()
  {
    $arrDetallePSG = $this->objModel->getCountDetailPrestamosSinGestion();

    $arrDetallePSGTMP = $arrDetallePSG;
    $decSUMAConteoTMP = 0;

    foreach( $arrDetallePSGTMP as $key => $val ){
      $intConteoTMP = $val["CONTEO"];
      $decSUMAConteoTMP = $decSUMAConteoTMP + $intConteoTMP;
    }

    $intCantidadPSG = 0;
    $decSaldoPSG = 0;
  ?>
    <table class="table table-sm table-hover table-borderless">
      <thead>
        <tr>
          <th style="text-align: center;">No. </th>
          <th style="text-align: center;">Agencia</th>
          <th style="text-align: center;">Porcentaje</th>
          <th style="text-align: center;">Cantidad</th>
          <th style="text-align: center;">Saldo sin gestión</th>
          <th style="text-align: center;">&nbsp;</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $intCount = 0;
        foreach( $arrDetallePSG as $key => $val ){
          $intCount++;
          $strAgencia = $key;
          $intCantidad = $val["CONTEO"];
          $decPorcentaje = ($intCantidad / $decSUMAConteoTMP) * 100;
          $intCantidadPSG = $intCantidadPSG + $intCantidad;
          $decSaldoSinGestion = $val["SALDO_SIN_GESTION"];
          $decSaldoPSG = $decSaldoPSG + $decSaldoSinGestion;
        ?>
          <tr>
            <td style="text-align: center; vertical-align:middle;">
              <h5><span class="badge bg-light-blue"><?php print $intCount; ?></span></h5>
            </td>
            <td style="text-align: center; vertical-align:middle;"><?php print $strAgencia; ?></td>
            <td style="text-align: center; vertical-align:middle;"><?php print number_format($decPorcentaje, 2) . '%'; ?></td>
            <td style="text-align: center; vertical-align:middle;"><?php print $intCantidad; ?></td>
            <td style="text-align: center; vertical-align:middle;"><?php print 'Q.' . number_format($decSaldoSinGestion, 2, '.', ','); ?></td>
          </tr>
        <?php
        }
        ?>
        <tr>
          <td>&nbsp;</td>
          <td style="text-align: right;"><b>Total..</b></td>
          <td style="text-align: center;"><b>100%</b></td>
          <td style="text-align: center;"><b><?php print $intCantidadPSG; ?></b></td>
          <td style="text-align: center; vertical-align:middle;"><b><?php print 'Q.' . number_format($decSaldoPSG, 2, '.', ','); ?></b></td>
        </tr>
      </tbody>
    </table>
  <?php
  }

  public function drawDetailCC()
  {
    $arrDetalleCC = $this->objModel->getCountDetailCuotaCero();

    $arrDetalleCCTMP = $arrDetalleCC;
    $decSUMAConteoTMP = 0;
    foreach( $arrDetalleCCTMP as $key => $val ){
      $intConteoTMP = $val["CONTEO"];
      $decSUMAConteoTMP = $decSUMAConteoTMP + $intConteoTMP;
    }

    $intCantidadCC = 0;
    $decSaldoCC = 0;
  ?>
    <table class="table table-sm table-hover table-borderless">
      <thead>
        <tr>
          <th style="text-align: center;">No. </th>
          <th style="text-align: center;">Agencia</th>
          <th style="text-align: center;">Porcentaje</th>
          <th style="text-align: center;">Cantidad</th>
          <th style="text-align: center;">Saldo sin gestión</th>
          <th style="text-align: center;">&nbsp;</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $intCount = 0;
        foreach( $arrDetalleCC as $key => $val ){
          $intCount++;
          $strAgencia = $key;
          $intCantidad = $val["CONTEO"];
          $decPorcentaje = ($intCantidad / $decSUMAConteoTMP) * 100;
          $intCantidadCC = $intCantidadCC + $intCantidad;
          $decSaldoSinGestion = $val["SALDO_SIN_GESTION"];
          $decSaldoCC = $decSaldoCC + $decSaldoSinGestion;
        ?>
          <tr>
            <td style="text-align: center; vertical-align:middle;">
              <h5><span class="badge bg-light-blue"><?php print $intCount; ?></span></h5>
            </td>
            <td style="text-align: center; vertical-align:middle;"><?php print $strAgencia; ?></td>
            <td style="text-align: center;"><?php print number_format($decPorcentaje, 2) . '%'; ?></td>
            <td style="text-align: center; vertical-align:middle;"><?php print $intCantidad; ?></td>
            <td style="text-align: center; vertical-align:middle;"><?php print 'Q.' . number_format($decSaldoSinGestion, 2, '.', ','); ?></td>
          </tr>
        <?php
        }
        ?>
        <tr>
          <td>&nbsp;</td>
          <td style="text-align: right;"><b>Total..</b></td>
          <td style="text-align: center;"><b>100%</b></td>
          <td style="text-align: center;"><b><?php print $intCantidadCC; ?></b></td>
          <td style="text-align: center; vertical-align:middle;"><b><?php print 'Q.' . number_format($decSaldoCC, 2, '.', ','); ?></b></td>
        </tr>
      </tbody>
    </table>
  <?php
  }

  public function drawDetailAPCG($intAgencia)
  {
    $arrDetalleAPCG = $this->objModel->getDetalleAPCG($intAgencia);

    $arrDetalleAPCGTMP = $arrDetalleAPCG;
    $decSUMAConteoTMP = 0;
    foreach( $arrDetalleAPCGTMP as $key => $val ){
      $intConteoTMP = $val["CONTEO"];
      $decSUMAConteoTMP = $decSUMAConteoTMP + $intConteoTMP;
    }

    $intCantidadAPCG = 0;
  ?>
    <table class="table table-sm table-hover table-borderless">
      <thead>
        <tr>
          <th style="text-align: center;">No. </th>
          <th style="text-align: center;">Usuario</th>
          <th style="text-align: center;">Porcentaje</th>
          <th style="text-align: center;">Cantidad</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $intCount = 0;
        foreach( $arrDetalleAPCG as $key => $val ){
          $intCount++;
          $strUsuario = $key;
          $intCantidad = $val["CONTEO"];
          $decPorcentaje = ($intCantidad / $decSUMAConteoTMP) * 100;
          $intCantidadAPCG = $intCantidadAPCG + $intCantidad;
        ?>
          <tr>
            <td style="text-align: center;">
              <h5><span class="badge bg-light-blue"><?php print $intCount; ?></span></h5>
            </td>
            <td style="text-align: center;"><?php print $strUsuario; ?></td>
            <td style="text-align: center;"><?php print number_format($decPorcentaje, 2) . '%'; ?></td>
            <td style="text-align: center;"><?php print $intCantidad; ?></td>
          </tr>
        <?php
        }
        ?>
        <tr>
          <td>&nbsp;</td>
          <td style="text-align: right;"><b>Total..</b></td>
          <td style="text-align: center;"><b>100%</b></td>
          <td style="text-align: center;"><b><?php print $intCantidadAPCG; ?></b></td>
        </tr>
      </tbody>
    </table>
  <?php
  }

  public function drawDetailIlocalizado()
  {
    $arrDetalle = $this->objModel->getDetalleIlocalizado();

    $arrDetalleTMP = $arrDetalle;
    $decSUMAConteoTMP = 0;
    foreach( $arrDetalleTMP as $key => $val ){
      $intConteoTMP = $val["CONTEO"];
      $decSUMAConteoTMP = $decSUMAConteoTMP + $intConteoTMP;
    }

    $intSuma = 0;
  ?>
    <table class="table table-sm table-hover table-borderless">
      <thead>
        <tr>
          <th style="text-align: center;">No. </th>
          <th style="text-align: center;">Clasificacion</th>
          <th style="text-align: center;">Porcentaje</th>
          <th style="text-align: center;">Conteo</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $intCount = 0;
        foreach( $arrDetalle as $key => $val ){
          $intCount++;
          $strClasificacion = utf8_encode($key);
          $intCantidad = intval($val["CONTEO"]);
          $decPorcentaje = ($intCantidad / $decSUMAConteoTMP) * 100;
          $intSuma = $intSuma + $intCantidad;
        ?>
          <tr>
            <td style="text-align: center;">
              <h5><span class="badge bg-green"><?php print $intCount; ?></span></h5>
            </td>
            <td style="text-align: center;"><?php print $strClasificacion; ?></td>
            <td style="text-align: center;"><?php print number_format($decPorcentaje, 2) . '%'; ?></td>
            <td style="text-align: center;"><?php print $intCantidad; ?></td>
          </tr>
        <?php
        }
        ?>
        <tr>
          <td>&nbsp;</td>
          <td style="text-align: right;"><b>Total..</b></td>
          <td style="text-align: center;"><b>100%</b></td>
          <td style="text-align: center;"><b><?php print $intSuma; ?></b></td>
        </tr>
      </tbody>
    </table>
  <?php
  }

  public function drawDetailLocalizado()
  {
    $arrDetalle = $this->objModel->getDetalleLocalizado();

    $arrDetalleTMP = $arrDetalle;
    $decSUMAConteoTMP = 0;
    foreach( $arrDetalleTMP as $key => $val ){
      $intConteoTMP = $val["CONTEO"];
      $decSUMAConteoTMP = $decSUMAConteoTMP + $intConteoTMP;
    }

    $intSuma = 0;
  ?>
    <table class="table table-sm table-hover table-borderless">
      <thead>
        <tr>
          <th style="text-align: center;">No. </th>
          <th style="text-align: center;">Clasificacion</th>
          <th style="text-align: center;">Porcentaje</th>
          <th style="text-align: center;">Conteo</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $intCount = 0;
        foreach( $arrDetalle as $key => $val ){
          $intCount++;
          $strClasificacion = utf8_encode($key);
          $intCantidad = intval($val["CONTEO"]);
          $decPorcentaje = ($intCantidad / $decSUMAConteoTMP) * 100;
          $intSuma = $intSuma + $intCantidad;
        ?>
          <tr>
            <td style="text-align: center;">
              <h5><span class="badge bg-green"><?php print $intCount; ?></span></h5>
            </td>
            <td style="text-align: center;"><?php print $strClasificacion; ?></td>
            <td style="text-align: center;"><?php print number_format($decPorcentaje, 2) . '%'; ?></td>
            <td style="text-align: center;"><?php print $intCantidad; ?></td>
          </tr>
        <?php
        }
        ?>
        <tr>
          <td>&nbsp;</td>
          <td style="text-align: right;"><b>Total..</b></td>
          <td style="text-align: center;"><b>100%</b></td>
          <td style="text-align: center;"><b><?php print $intSuma; ?></b></td>
        </tr>
      </tbody>
    </table>
  <?php
  }

  public function drawDetailIrrecuperable()
  {
    $arrDetalle = $this->objModel->getDetalleIrrecuperable();

    $arrDetalleTMP = $arrDetalle;
    $decSUMAConteoTMP = 0;
    foreach( $arrDetalleTMP as $key => $val ){
      $intConteoTMP = $val["CONTEO"];
      $decSUMAConteoTMP = $decSUMAConteoTMP + $intConteoTMP;
    }

    $intSuma = 0;
  ?>
    <table class="table table-sm table-hover table-borderless">
      <thead>
        <tr>
          <th style="text-align: center;">No. </th>
          <th style="text-align: center;">Clasificacion</th>
          <th style="text-align: center;">Porcentaje</th>
          <th style="text-align: center;">Conteo</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $intCount = 0;
        foreach( $arrDetalle as $key => $val ){
          $intCount++;
          $strClasificacion = utf8_encode($key);
          $intCantidad = intval($val["CONTEO"]);
          $decPorcentaje = ($intCantidad / $decSUMAConteoTMP) * 100;
          $intSuma = $intSuma + $intCantidad;
        ?>
          <tr>
            <td style="text-align: center;">
              <h5><span class="badge bg-green"><?php print $intCount; ?></span></h5>
            </td>
            <td style="text-align: center;"><?php print $strClasificacion; ?></td>
            <td style="text-align: center;"><?php print number_format($decPorcentaje, 2) . '%'; ?></td>
            <td style="text-align: center;"><?php print $intCantidad; ?></td>
          </tr>
        <?php
        }
        ?>
        <tr>
          <td>&nbsp;</td>
          <td style="text-align: right;"><b>Total..</b></td>
          <td style="text-align: center;"><b>100%</b></td>
          <td style="text-align: center;"><b><?php print $intSuma; ?></b></td>
        </tr>
      </tbody>
    </table>
    <?php
  }

  public function drawBlurDetailContent($arrDetail)
  {
    if (count($arrDetail) > 0) {
      $intPrestamo = $arrDetail["IDPRESTAMO"];
      $strNombreAgencia = $arrDetail["NOMBREAGENCIA"];
      $intNumeroPrestamo = $arrDetail["NUMERO"];
      $strEstadoPrestamo = $arrDetail["ESTADO_PRESTAMO"];
      $intDiasMoraCapital = $arrDetail["DIAS_MORA_CAPITAL"];

      //Datos del asociado
      $strNombres = $arrDetail["NOMBRES"];
    ?>
      <table class="table table-borderless table-sm">
        <!-- datos del asociado-->
        <tr>
          <td colspan="4">
            <b>Nombre Completo</b>: <?php print ucwords($strNombres); ?>
            <input type="hidden" id="hdnPrestamoEvaluar" value="<?php print $intNumeroPrestamo; ?>">
          </td>
        </tr>
        <tr>
          <td colspan="4"><b>Agencia</b>: <?php print $strNombreAgencia; ?></td>
        </tr>
        <tr>
          <td colspan="4"><b>Numero préstamo</b>: <?php print $intNumeroPrestamo; ?></td>
        </tr>
        <!-- datos del asociado-->
      </table>
      <br><br>
    <?php
    } else {
    ?>
      <h2>No se encontraron resultados, verifique si el número de préstamo es correcto.</h2>
    <?php
    }
  }

  public function drawContent()
  {
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
        .centrar {
          position: absolute;
          /*nos posicionamos en el centro del navegador*/
          top: 50%;
          left: 50%;
          float: none;
          /*determinamos una anchura*/
          width: 400px;
          /*indicamos que el margen izquierdo, es la mitad de la anchura*/
          margin-left: -130px;
          /*determinamos una altura*/
          height: 300px;
          /*indicamos que el margen superior, es la mitad de la altura*/
          margin-top: -150px;
          padding: 5px;
          z-index: 1;
        }

        @media only screen and (max-width: 800px) {

          /* Force table to not be like tables anymore */
          #no-more-tables table,
          #no-more-tables thead,
          #no-more-tables tbody,
          #no-more-tables th,
          #no-more-tables td,
          #no-more-tables tr {
            display: block;
          }

          /* Hide table headers (but not display: none;, for accessibility) */
          #no-more-tables thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
          }

          #no-more-tables tr {
            border: 1px solid #ccc;
          }

          #no-more-tables td {
            /* Behave like a "row" */
            border: none;
            border-bottom: 1px solid #eee;
            position: relative;
            padding-left: 50%;
            white-space: normal;
            text-align: left;
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
            text-align: left;
            font-weight: bold;
          }

          /*
          Label the data
          */
          #no-more-tables td:before {
            content: attr(data-title);
          }
        }
      </style>
    </head>

    <body class="hold-transition skin-blue sidebar-mini">
      <?php $this->drawBlurDetailSaldoCapital(); ?>
      <?php $this->drawBlurDetailPrestamoPremora(); ?>
      <?php $this->drawBlurDetailPCG(); ?>
      <?php $this->drawBlurDetailPSG(); ?>
      <?php $this->drawBlurDetailAPCG(); ?>
      <?php $this->drawBlurDetailCuotaCero(); ?>
      <?php $this->drawBlurDetailIlocalizado(); ?>
      <?php $this->drawBlurDetailLocalizado(); ?>
      <?php $this->drawBlurDetailIrrecuperable(); ?>
      <?php $this->drawBlurDetailFromPremora(); ?>
      <?php $this->drawBlurDetalleBusqueda(); ?>
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
            <form method="POST" class="sidebar-form" action="javascript:void(0);">
              <div class="input-group">
                <input type="text" id="txtsearchprestamo" name="txtsearchprestamo" class="form-control" placeholder="Buscar">
                <span class="input-group-btn">
                  <button id="btnsearchprestamo" name="btnsearchprestamo" class="btn btn-flat" onclick="searchPremora()"><i class="fa fa-search"></i></button>
                </span>
              </div>
            </form>
            <!-- sidebar menu: : style can be found in sidebar.less -->
            <?php draMenu($this->arrRolUser); ?>
          </section>
          <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
          <!-- Content Header (Page header) -->
          <br>
          <section class="content-header">
            <div class="alert alert-success alert-dismissible">
              <h4><i class="icon fa fa-check"></i> Ultima carga de datos</h4>
              <?php
              $strFechaUltimaCarga = getDateUpdateData();
              ?>
              Ultima carga de datos al <?php print $strFechaUltimaCarga; ?>.
            </div>
          </section>
          <br><br>
          <!-- Main content -->
          <section class="content">
            <div class="box box-solid">
              <!-- /.box-header -->
              <div class="box-body">
                <div class="box-group" id="accordion">
                  <!-- we are adding the .panel class so bootstrap.js collapse plugin detects it -->
                  <div class="panel box box-primary">
                    <div class="box-header with-border" style="background-color: #dd4b39;">
                      <h4 class="box-title">
                        <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" style="color:white;">
                          <i class="fa fa-fw fa-unsorted"></i> Estadisticas Préstamos más Altos en Premora
                        </a>
                      </h4>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse" aria-expanded="false" style="">
                      <div class="box-body">
                        <div id="no-more-tables">
                          <table class="table table-sm table-hover table-condensed table-striped" id="tblListadoRA">
                            <thead class="cf">
                              <tr style="background-color: #dd4b39; color:white;">
                                <th style="text-align:center;">No.</th>
                                <th style="text-align:center;">Agencia</th>
                                <th style="text-align:center;">Numero Préstamo</th>
                                <th style="text-align:center;">Nombres</th>
                                <th style="text-align:center;">Gestiones Mes Actual</th>
                                <th style="text-align:center;">&nbsp;</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php
                              $arrListadoRA = $this->objModel->getListadoRiesgoAlto();
                              $intCount = 0;
                              if (count($arrListadoRA) > 0) {
                                foreach( $arrListadoRA as $key => $val ){
                                  $intCount++;
                                  $intId = $key;
                                  $intNumeroPrestamo = $val["NUMERO"];
                                  $strAgencia = utf8_encode($val["AGENCIA"]);
                                  $strNombres = utf8_encode($val["NOMBRES"]);
                                  $intConteoPromesas = intval($val["PROMESAS"]);
                              ?>
                                  <tr id="trId_<?php print $intId; ?>">
                                    <td data-title="No." style="text-align:center; vertical-align:middle;">
                                      <h5><span class="badge bg-red"><?php print $intCount; ?></span></h5>
                                    </td>
                                    <td data-title="Agencia" style="text-align:center; vertical-align:middle;">
                                      <?php print $strAgencia; ?>
                                    </td>
                                    <td data-title="Número de Prestamo" style="text-align:center; vertical-align:middle;">
                                      <?php print $intNumeroPrestamo; ?>
                                    </td>
                                    <td data-title="Nombres" style="text-align:center; vertical-align:middle;">
                                      <?php print ucwords($strNombres); ?>
                                    </td>
                                    <td data-title="Gestiones Registradas" style="text-align:center; vertical-align:middle;">
                                      <?php print $intConteoPromesas; ?>
                                    </td>
                                    <td data-title="Detalles" style="text-align:center; vertical-align:middle;">
                                      <button class="btn btn-danger btn-raised" title="Ver detalles" onclick="redirectListRiskHigh('<?php print $intId; ?>');">
                                        <i class="fa fa-search" aria-hidden="true"></i> Ver Detalles
                                      </button>
                                    </td>
                                  </tr>
                              <?php
                                }
                              }
                              ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- /.box-body -->
            </div>
          </section>
          <br><br>
          <section class="content-header">
            <h1>Estadisticas Por Tipo De Riesgo</h1>
          </section>
          <!-- Main content -->
          <section class="content">
            <!-- Small boxes (Stat box) -->
            <div class="row">
              <?php
              $arrEstadisticas = $this->objModel->getEstadisticas();
              if (count($arrEstadisticas) > 0) {
              ?>
                <!-- Riesgo Alto -->
                <div class="col-lg-4 col-xs-12">
                  <!-- small box -->
                  <div class="small-box" style="background-color: <?php print $arrEstadisticas["RIESGO_ALTO_COLOR"]; ?>;">
                    <div class="inner" style="color:white;">
                      <h3><?php print $arrEstadisticas["RIESGO_ALTO_CONTEO"]; ?></h3>
                      <p>
                      <h4>Riesgo Alto</h4>
                      </p>
                    </div>
                    <div class="icon">
                      <i class="ion ion-stats-bars"></i>
                    </div>
                    <a href="listado_riesgo_alto.php" class="small-box-footer">Más información <i class="fa fa-arrow-circle-right"></i></a>
                  </div>
                </div>
                <!-- Riesgo Alto -->
                <!-- Riesgo Medio -->
                <div class="col-lg-4 col-xs-12">
                  <!-- small box -->
                  <div class="small-box" style="background-color: <?php print $arrEstadisticas["RIESGO_MEDIO_COLOR"]; ?>;">
                    <div class="inner" style="color:white;">
                      <h3><?php print $arrEstadisticas["RIESGO_MEDIO_CONTEO"]; ?></h3>
                      <p>
                      <h4>Riesgo Medio</h4>
                      </p>
                    </div>
                    <div class="icon">
                      <i class="ion ion-stats-bars"></i>
                    </div>
                    <a href="listado_riesgo_medio.php" class="small-box-footer">Más información <i class="fa fa-arrow-circle-right"></i></a>
                  </div>
                </div>
                <!-- Riesgo Medio -->
                <!-- Riesgo Bajo -->
                <div class="col-lg-4 col-xs-12">
                  <!-- small box -->
                  <div class="small-box" style="background-color: <?php print $arrEstadisticas["RIESGO_BAJO_COLOR"]; ?>;">
                    <div class="inner" style="color:white;">
                      <h3><?php print $arrEstadisticas["RIESGO_BAJO_CONTEO"]; ?></h3>
                      <p>
                      <h4>Riesgo Bajo</h4>
                      </p>
                    </div>
                    <div class="icon">
                      <i class="ion ion-stats-bars"></i>
                    </div>
                    <a href="listado_riesgo_bajo.php" class="small-box-footer">Más información <i class="fa fa-arrow-circle-right"></i></a>
                  </div>
                </div>
                <!-- Riesgo Bajo -->
                <?php
                if (isset($this->arrRolUser["MORA"]) && $this->arrRolUser["MORA"] == true) {
                ?>
                  <!-- From premora -->
                  <div class="col-lg-4 col-xs-12">
                    <!-- small box -->
                    <div class="small-box bg-aqua">
                      <div class="inner" style="color:white;">
                        <h3><?php print $arrEstadisticas["CONTEO_FROM_PREMORA"]; ?></h3>
                        <p>
                        <h4>Préstamos desde premora</h4>
                        </p>
                      </div>
                      <div class="icon">
                        <i class="ion ion-stats-bars"></i>
                      </div>
                      <a class="small-box-footer" style="cursor:pointer;" onclick="openModalDetailFromPremora()">
                        Más información <i class="fa fa-arrow-circle-right"></i>
                      </a>
                    </div>
                  </div>
                  <!-- From Premora -->
              <?php
                }
              }
              ?>
            </div>
          </section>
          <section class="content-header">
            <h1>Estadisticas préstamos premora</h1>
          </section>
          <!-- Main content -->
          <section class="content">
            <!-- Small boxes (Stat box) -->
            <div class="row">

              <!-- Saldo Premora -->
              <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                <!-- small box -->
                <div class="small-box bg-aqua">
                  <div class="inner" style="color:white;">
                    <?php
                    $intTotalSaldoPremora = $this->objModel->getTotalSaldoPremora();
                    ?>
                    <h3><?php print 'Q.' . number_format($intTotalSaldoPremora, 2, '.', ','); ?></h3>
                    <p>
                    <h4>Saldo Premora</h4>
                    </p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-stats-bars"></i>
                  </div>
                  <a class="small-box-footer" style="cursor:pointer;" onclick="openModalDetailSaldoCapital()">Más información <i class="fa fa-arrow-circle-right"></i></a>
                </div>
              </div>
              <!-- Saldo Premora -->
              <!-- Cantidad préstamos premora -->
              <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                <!-- small box -->
                <div class="small-box bg-aqua">
                  <div class="inner" style="color:white;">
                    <?php
                    $intCantidadPrestamosPremora = $this->objModel->getCantidadPrestamosPremora();
                    ?>
                    <h3><?php print $intCantidadPrestamosPremora; ?></h3>
                    <p>
                    <h4>Cantidad préstamos premora</h4>
                    </p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-stats-bars"></i>
                  </div>
                  <a style="cursor:pointer;" class="small-box-footer" onclick="openModalDetailPrestamosPremora()">Más información <i class="fa fa-arrow-circle-right"></i></a>
                </div>
              </div>
              <!-- Cantidad préstamos premora -->
              <!-- Cantidad de creditos con gestión -->
              <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                <!-- small box -->
                <div class="small-box bg-aqua">
                  <div class="inner" style="color:white;">
                    <?php
                    $intCountPrestamosConGestion = $this->objModel->getCountPrestamosConGestion();
                    ?>
                    <h3><?php print $intCountPrestamosConGestion; ?></h3>
                    <p>
                    <h4>Cantidad de préstamos con gestión</h4>
                    </p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-stats-bars"></i>
                  </div>
                  <a style="cursor:pointer;" class="small-box-footer" onclick="openModalDetailPCG()">Más información <i class="fa fa-arrow-circle-right"></i></a>
                </div>
              </div>
              <!-- Cantidad de creditos con gestión -->
              <!-- Cantidad de creditos sin gestión -->
              <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                <!-- small box -->
                <div class="small-box bg-aqua">
                  <div class="inner" style="color:white;">
                    <?php
                    $intCountPrestamosSinGestion = $this->objModel->getCountPrestamosSinGestion();
                    ?>
                    <h3><?php print $intCountPrestamosSinGestion; ?></h3>
                    <p>
                    <h4>Cantidad de préstamos sin gestión</h4>
                    </p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-stats-bars"></i>
                  </div>
                  <a style="cursor:pointer;" class="small-box-footer" onclick="openModalDetailPSG()">Más información <i class="fa fa-arrow-circle-right"></i></a>
                </div>
              </div>
              <!-- Cantidad de creditos sin gestión -->
              <!-- Cantidad de creditos Cuota Cero -->
              <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                <!-- small box -->
                <div class="small-box bg-aqua">
                  <div class="inner" style="color:white;">
                    <?php
                    $intCountCuotaCero = $this->objModel->getCountPrestamosCuotaCero();
                    ?>
                    <h3><?php print $intCountCuotaCero; ?></h3>
                    <p>
                    <h4>Cantidad de préstamos Cuota Cero</h4>
                    </p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-stats-bars"></i>
                  </div>
                  <a style="cursor:pointer;" class="small-box-footer" onclick="openModalDetailCC()">Más información <i class="fa fa-arrow-circle-right"></i></a>
                </div>
              </div>
              <!-- Cantidad de creditos Cuota Cero -->

            </div>
          </section>
          <!-- /.content -->
          <br>
          <section class="content-header">
            <h1>Estadisticas Por Clasificación de Cobro</h1>
          </section>
          <section class="content">
            <div class="row">
              <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                <!-- small box -->
                <div class="small-box bg-green">
                  <div class="inner" style="color:white;">
                    <?php
                    $intConteoIlocalizado = $this->objModel->getConteoIlocalizado();
                    ?>
                    <h3><?php print $intConteoIlocalizado; ?></h3>
                    <p>
                    <h4>Ilocalizado</h4>
                    </p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-stats-bars"></i>
                  </div>
                  <a class="small-box-footer" style="cursor:pointer;" onclick="openModalDetailIlocalizado()">Más información <i class="fa fa-arrow-circle-right"></i></a>
                </div>
              </div>
              <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                <!-- small box -->
                <div class="small-box bg-green">
                  <div class="inner" style="color:white;">
                    <?php
                    $intConteoLocalizado = $this->objModel->getConteoLocalizado();
                    ?>
                    <h3><?php print $intConteoLocalizado; ?></h3>
                    <p>
                    <h4>Localizado</h4>
                    </p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-stats-bars"></i>
                  </div>
                  <a class="small-box-footer" style="cursor:pointer;" onclick="openModalDetailLocalizado()">Más información <i class="fa fa-arrow-circle-right"></i></a>
                </div>
              </div>
              <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                <!-- small box -->
                <div class="small-box bg-green">
                  <div class="inner" style="color:white;">
                    <?php
                    $intConteoIrrecuperable = $this->objModel->getConteoIrrecuperable();
                    ?>
                    <h3><?php print $intConteoIrrecuperable; ?></h3>
                    <p>
                    <h4>Irrecuperable</h4>
                    </p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-stats-bars"></i>
                  </div>
                  <a class="small-box-footer" style="cursor:pointer;" onclick="openModalDetailIrrecuperable()">Más información <i class="fa fa-arrow-circle-right"></i></a>
                </div>
              </div>
            </div>
          </section>
          <!-- /.content -->
          <br>
          <?php
          if ((isset($this->arrRolUser["NORMAL"]) && $this->arrRolUser["NORMAL"] == true) || (isset($this->arrRolUser["MASTER"]) && $this->arrRolUser["MASTER"] == true)) {
          ?>
            <section class="content-header">
              <h1>Graficas estadisticas</h1>
            </section>
            <section class="content">
              <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                  <!-- Inicio Graficas -->
                  <div class="col-md-12">
                    <!-- Line chart -->
                    <div class="box box-primary">
                      <div class="box-header with-border">
                        <i class="fa fa-bar-chart-o"></i>
                        <h3 class="box-title">Historial Saldo Premora</h3>
                        <div class="box-tools pull-right">
                          <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                        </div>
                      </div>
                      <div class="box-body">
                        <script src="dist/js/loader.js"></script>
                        <script type="text/javascript">
                          google.charts.load('current', {
                            'packages': ['corechart']
                          });
                          google.charts.setOnLoadCallback(drawChart);

                          function drawChart() {
                            var data = google.visualization.arrayToDataTable([
                              ['Fecha', 'Saldo Premora'],
                              <?php
                              $arrLogSaldoPremora = $this->objModel->getLogSaldoPremora();
                              $intCount = count($arrLogSaldoPremora);
                              $intCorre = 0;
                              $decTotalSaldo = 0;
                              foreach( $arrLogSaldoPremora as $key => $val ){
                                $intCorre++;
                                $strFecha = $key;
                                $decSaldo = $val["SALDO"];
                                $decTotalSaldo = $decTotalSaldo + $decSaldo;
                                if ($intCorre == $intCount) {
                              ?>['<?php print $strFecha; ?>', <?php print $decSaldo; ?>]
                                <?php
                                } else {
                                ?>['<?php print $strFecha; ?>', <?php print $decSaldo; ?>],
                              <?php
                                }
                              }

                              $decPromedioSaldo = $decTotalSaldo / $intCount;
                              ?>
                            ]);

                            var options = {
                              title: 'Historial Saldo Premora',
                              curveType: 'function',
                              bar: {
                                groupWidth: "95%"
                              },
                              visibleInLegend: true,
                              pointsVisible: true,
                              legend: {
                                position: 'bottom'
                              }
                            };

                            var chart = new google.visualization.LineChart(document.getElementById('chart_saldo_premora'));
                            chart.draw(data, options);
                          }
                        </script>
                        <div id="chart_saldo_premora" style="width: auto; height: auto;"></div><br>
                        <div style="text-align:center;">
                          <h4> Promedio: <?php print 'Q.' . number_format($decPromedioSaldo, 2, '.', ','); ?></h4>
                        </div>
                      </div>
                      <!-- /.box-body-->
                    </div>
                    <!-- /.box -->
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                  <div class="col-md-12">
                    <div class="box box-primary">
                      <div class="box-header with-border">
                        <i class="fa fa-bar-chart-o"></i>
                        <h3 class="box-title">Historial Conteo Gestiones de Cobro por día</h3>
                        <div class="box-tools pull-right">
                          <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                        </div>
                      </div>
                      <div class="box-body">
                        <script src="dist/js/loader.js"></script>
                        <script type="text/javascript">
                          google.charts.load('current', {
                            'packages': ['corechart']
                          });
                          google.charts.setOnLoadCallback(drawChart2);

                          function drawChart2() {
                            var data = google.visualization.arrayToDataTable([
                              ['Fecha', 'Gestiones de cobro por día'],
                              <?php
                              $arrConteoGestiones = $this->objModel->getConteoGestionesDate();
                              $intCount2 = count($arrConteoGestiones);
                              $intCorre2 = 0;
                              $decTotalConteo = 0;
                              foreach( $arrConteoGestiones as $key => $val ){
                                $intCorre++;
                                $strFecha2 = $key;
                                $intConteo2 = $val["CONTEO"];
                                $decTotalConteo = $decTotalConteo + $intConteo2;
                                if ($intCorre2 == $intCount2) {
                              ?>['<?php print $strFecha2; ?>', <?php print $intConteo2; ?>]
                                <?php
                                } else {
                                ?>['<?php print $strFecha2; ?>', <?php print $intConteo2; ?>],
                              <?php
                                }
                              }
                              $decPromedioConteo = intval($decTotalConteo / $intCount2);
                              ?>
                            ]);

                            var options = {
                              title: 'Historial Conteo Gestiones de Cobro por día',
                              curveType: 'function',
                              bar: {
                                groupWidth: "95%"
                              },
                              visibleInLegend: true,
                              pointsVisible: true,
                              legend: {
                                position: 'bottom'
                              }
                            };

                            var chart = new google.visualization.LineChart(document.getElementById('chart_gestiones'));
                            chart.draw(data, options);
                          }
                        </script>
                        <div id="chart_gestiones" style="width: auto; height: auto;"></div><br>
                        <div style="text-align:center;">
                          <h4> Promedio: <?php print $decPromedioConteo; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Fin Graficas -->
            </section>
          <?php
          }
          ?>
        </div>
        <!-- /.content-wrapper -->
        <footer class="main-footer">
          <div class="pull-right hidden-xs">
            <b>Version</b> 1.0
          </div>
          <strong>Copyright &copy; <?php print date('Y')?>
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

        function destroSession() {
          if (confirm("¿Desea salir de la aplicación?")) {
            $.ajax({
              url: "dashboard.php",
              data: {
                destroSession: true
              },
              type: "post",
              dataType: "json",
              success: function(data) {
                if (data.Correcto == "Y") {
                  alert("Usted ha cerrado sesión");
                  location.href = "index.php";
                }
              }
            });
          }
        }

        function searchPremora() {
          var intPrestamo = $("#txtsearchprestamo").val();
          if (intPrestamo > 0) {
            $.ajax({
              url: "dashboard.php",
              data: {
                searchPremora: true,
                intPrestamo: intPrestamo
              },
              type: "post",
              dataType: "html",
              success: function(data) {
                $('#modalBlurDetalleBusqueda').modal();
                $("#divContentModalBlurDetalleBusqueda").html('');
                $("#divContentModalBlurDetalleBusqueda").html(data);
              }
            });
          }
        }

        function redirectListado() {
          var intPrestamo = $("#txtsearchprestamo").val();
          if (intPrestamo > 0) {
            $.ajax({
              url: "dashboard.php",
              data: {
                redirectListado: true,
                intPrestamo: intPrestamo
              },
              type: "post",
              dataType: "json",
              success: function(data) {
                if (data.Tipo == "Bajo") {
                  location.href = "listado_riesgo_bajo.php?#trId_" + data.Idprestamo;
                }

                if (data.Tipo == "Medio") {
                  location.href = "listado_riesgo_medio.php?#trId_" + data.Idprestamo;
                }

                if (data.Tipo == "Alto") {
                  location.href = "listado_riesgo_alto.php?#trId_" + data.Idprestamo;
                }
              }
            });
          }
        }

        function openModalDetailSaldoCapital() {
          $.ajax({
            url: "dashboard.php",
            data: {
              getDetailSaldoCapital: true,
            },
            type: "post",
            dataType: "html",
            success: function(data) {
              $('#modalContentDetailSaldoCapital').modal();
              $("#divContentModalDetailSaldoCapital").html('');
              $("#divContentModalDetailSaldoCapital").html(data);
            }
          });
        }

        function openModalDetailPrestamosPremora() {
          $.ajax({
            url: "dashboard.php",
            data: {
              getDetailPrestamoPremora: true,
            },
            type: "post",
            dataType: "html",
            success: function(data) {
              $('#modalContentDetailPrestamoPremora').modal();
              $("#divContentModalDetailPrestamoPremora").html('');
              $("#divContentModalDetailPrestamoPremora").html(data);
            }
          });
        }

        function openModalDetailPCG() {
          $.ajax({
            url: "dashboard.php",
            data: {
              getDetailPCG: true,
            },
            type: "post",
            dataType: "html",
            success: function(data) {
              $('#modalContentDetailPCG').modal();
              $("#divContentModalDetailPCG").html('');
              $("#divContentModalDetailPCG").html(data);
            }
          });
        }

        function openModalDetailPSG() {
          $.ajax({
            url: "dashboard.php",
            data: {
              getDetailPSG: true,
            },
            type: "post",
            dataType: "html",
            success: function(data) {
              $('#modalContentDetailPSG').modal();
              $("#divContentModalDetailPSG").html('');
              $("#divContentModalDetailPSG").html(data);
            }
          });
        }

        function openModalDetailAPCG(intAgencia) {
          $.ajax({
            url: "dashboard.php",
            data: {
              getDetailAPCG: true,
              intAgencia: intAgencia
            },
            type: "post",
            dataType: "html",
            success: function(data) {
              $('#modalContentDetailAPCG').modal();
              $("#divContentModalDetailAPCG").html('');
              $("#divContentModalDetailAPCG").html(data);
            }
          });
        }

        function openModalDetailCC() {
          $.ajax({
            url: "dashboard.php",
            data: {
              getDetailCC: true
            },
            type: "post",
            dataType: "html",
            success: function(data) {
              $('#modalContentDetailCC').modal();
              $("#divContentModalDetailCC").html('');
              $("#divContentModalDetailCC").html(data);
            }
          });
        }

        function openModalDetailIlocalizado() {
          $.ajax({
            url: "dashboard.php",
            data: {
              getDetailIlocalizado: true
            },
            type: "post",
            dataType: "html",
            success: function(data) {
              $('#modalContentDetailIlocalizado').modal();
              $("#divContentModalDetailIlocalizado").html('');
              $("#divContentModalDetailIlocalizado").html(data);
            }
          });
        }

        function openModalDetailLocalizado() {
          $.ajax({
            url: "dashboard.php",
            data: {
              getDetailLocalizado: true
            },
            type: "post",
            dataType: "html",
            success: function(data) {
              $('#modalContentDetailLocalizado').modal();
              $("#divContentModalDetailLocalizado").html('');
              $("#divContentModalDetailLocalizado").html(data);
            }
          });
        }

        function openModalDetailIrrecuperable() {
          $.ajax({
            url: "dashboard.php",
            data: {
              getDetailIrrecuperable: true
            },
            type: "post",
            dataType: "html",
            success: function(data) {
              $('#modalContentDetailIrrecuperable').modal();
              $("#divContentModalDetailIrrecuperable").html('');
              $("#divContentModalDetailIrrecuperable").html(data);
            }
          });
        }

        function openModalDetailFromPremora() {
          $.ajax({
            url: "dashboard.php",
            data: {
              getDetailFromPremora: true
            },
            type: "post",
            dataType: "html",
            success: function(data) {
              $('#modalContentDetailDesdePremora').modal();
              $("#divContentModalDetailDesdePremora").html('');
              $("#divContentModalDetailDesdePremora").html(data);
            }
          });
        }

        function redirectListRiskHigh(id) {
          $.ajax({
            url: "dashboard.php",
            data: {
              redirectListRiskHigh: true
            },
            type: "post",
            dataType: "json",
            success: function(data) {
              location.href = "listado_riesgo_alto.php?#trId_" + id;
            }
          });
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