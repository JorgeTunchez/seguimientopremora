<?php
require_once("core/core.php");
error_reporting(E_ALL);
ini_set('display_errors', 0);
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
      header("Location: dashboard.php");
    } elseif ($strRolUserSession == "mora") {
      $arrRolUser["MORA"] = true;
      header("Location: dashboard.php");
    }
  }
} else {
  header("Location: index.php");
}

$objController = new rpt_hp_controller($arrRolUser);
$objController->runAjax();
$objController->drawContentController();

class rpt_hp_controller
{

  private $objModel;
  private $objView;
  private $arrRolUser;

  public function __construct($arrRolUser)
  {
    $this->objModel = new rpt_hp_model($arrRolUser);
    $this->objView = new rpt_hp_view($arrRolUser);
    $this->arrRolUser = $arrRolUser;
  }

  public function drawContentController()
  {
    $this->objView->drawContent();
  }

  public function runAjax()
  {
    $this->exportPDF();
    $this->ajaxDestroySession();
    $this->ajaxgetDetailReporte();
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

  public function ajaxgetDetailReporte()
  {
    if (isset($_POST["sltMes"]) && $_POST["sltMes"] != '') {
      $intUsuario = isset($_POST["selectUsuarios"]) ? intval($_POST["selectUsuarios"]) : 0;
      $strMes = isset($_POST["sltMes"]) ? $_POST["sltMes"] : "";
      $strAnio = isset($_POST["sltAnio"]) ? intval($_POST["sltAnio"]) : "";
      $strMesAnio = $strMes . '-' . $strAnio;
      $arrDetail = $this->objModel->getHistorialPremora($intUsuario, $strMesAnio);
      if (count($arrDetail) > 0) {
?>
        <script>
          $("#btnExportarExcel").show();
        </script>
      <?php
      } else {
      ?>
        <script>
          $("#btnExportarExcel").hide();
        </script>
    <?php
      }

      print $this->objView->drawContentDetail($arrDetail);
      exit();
    }
  }

  public function exportPDF()
  {
    if (isset($_POST["hdnExportar"]) && $_POST["hdnExportar"] == 'true') {
      require_once("tcpdf/tcpdf.php");
      $strTipoExportar = isset($_POST["TipoExport"]) ? $_POST["TipoExport"] : '';
      $strNombreArchivo = "ReporteHistorialPremora_" . date("d") . "/" . date("m") . "/" . date("Y");

      //Validacion tipo de formato exportado
      if ($strTipoExportar == 'EXCEL') {
        $intUsuario = isset($_POST["selectUsuarios"]) ? intval($_POST["selectUsuarios"]) : 0;
        $strMes = isset($_POST["sltMes"]) ? $_POST["sltMes"] : "";
        $strAnio = isset($_POST["sltAnio"]) ? intval($_POST["sltAnio"]) : "";
        $strMesAnio = $strMes . '-' . $strAnio;
        $arrDetail = $this->objModel->getHistorialPremora($intUsuario, $strMesAnio);

        header("Pragma: no-cache");
        header('Cache-control: ');
        header("Expires: Mon, 26 Jul 2027 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Content-type: application/ms-excel");
        header("Content-disposition: attachment; filename={$strNombreArchivo}.xls");
        print utf8_encode($this->objView->drawContentDetail($arrDetail, true));
        exit();
      }
    }
  }
}

class rpt_hp_model
{

  private $arrRolUser;

  public function __construct($arrRolUser)
  {
    $this->arrRolUser = $arrRolUser;
  }

  public function getUsuarios()
  {
    $conn = getConexion();
    $arrUsuarios = array();
    $strQuery = "SELECT id, nombre FROM usuarios WHERE tipo = 2 AND activo = 1 ORDER BY nombre";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $arrUsuarios[$row["id"]]["NOMBRE"] = $row["nombre"];
      }
    }

    mysqli_close($conn);
    return $arrUsuarios;
  }


  public function getHistorialPremora($intUsuario, $strMesAnio)
  {
    if ($strMesAnio != '') {
      $arrHistorialPremora = array();
      $strAndUsuario = ($intUsuario != 0) ? "AND usuarios.id = {$intUsuario}" : "";
      $conn = getConexion();
      $strQuery = "SELECT (SELECT usuarios.nombre 
                            FROM usuarios 
                                 INNER JOIN usuario_agencias ON usuario_agencias.usuario = usuarios.id 
                           WHERE usuario_agencias.agencia = historial_premora.codigo_agencia) nombreusuario,
                         'Con gestion' gestion,
                                 (SELECT CASE 
                                         WHEN listado_condiciones.dias_mora_capital = 0 THEN 'Al dia'
                                         WHEN listado_condiciones.dias_mora_capital >0 AND listado_condiciones.dias_mora_capital <=30 THEN 'Premora'
                                         WHEN listado_condiciones.dias_mora_capital >30 THEN 'En mora'
                                         WHEN listado_condiciones.dias_mora_capital = NULL THEN 'Cancelado'
                                         WHEN listado_condiciones.dias_mora_capital = '' THEN 'Cancelado'
                                         ELSE 'Cancelado' END AS estado
                                    FROM listado_condiciones 
                                   WHERE listado_condiciones.numero_prestamo = historial_premora.numero_prestamo) estado,
                          COUNT(historial_premora.id) conteo,
                          SUM(historial_premora.saldo_actual) saldo
                     FROM historial_premora 
                    WHERE DATE_FORMAT(historial_premora.add_fecha, '%m-%Y') = '{$strMesAnio}'
                      AND historial_premora.numero_prestamo IN (SELECT DISTINCT prestamo FROM promesa_pago WHERE DATE_FORMAT(add_fecha, '%m-%Y') = '{$strMesAnio}' )
                    GROUP BY nombreusuario, estado
                  UNION(
                  SELECT (SELECT usuarios.nombre 
                            FROM usuarios 
                                 INNER JOIN usuario_agencias ON usuario_agencias.usuario = usuarios.id 
                           WHERE usuario_agencias.agencia = historial_premora.codigo_agencia) nombreusuario,
                         'Sin gestion' gestion,
                                 (SELECT CASE WHEN listado_condiciones.dias_mora_capital = 0 THEN 'Al dia'
                                           WHEN listado_condiciones.dias_mora_capital >0 AND listado_condiciones.dias_mora_capital <=30 THEN 'Premora'
                                           WHEN listado_condiciones.dias_mora_capital >30 THEN 'En mora'
                                           WHEN listado_condiciones.dias_mora_capital = NULL THEN 'Cancelado'
                                           WHEN listado_condiciones.dias_mora_capital = '' THEN 'Cancelado'
                                           ELSE 'Cancelado' END AS estado
                                  FROM listado_condiciones 
                                 WHERE listado_condiciones.numero_prestamo = historial_premora.numero_prestamo) estado,
                         COUNT(historial_premora.id) conteo,
                         SUM(historial_premora.saldo_actual) saldo
                    FROM historial_premora 
                   WHERE DATE_FORMAT(historial_premora.add_fecha, '%m-%Y') = '{$strMesAnio}'
                     AND historial_premora.numero_prestamo NOT IN (SELECT DISTINCT prestamo FROM promesa_pago WHERE DATE_FORMAT(add_fecha, '%m-%Y') = '{$strMesAnio}')
                   GROUP BY nombreusuario, estado
                   )
                   ORDER BY nombreusuario, estado";
      $result = mysqli_query($conn, $strQuery);
      if (!empty($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
          $arrHistorialPremora[$row["nombreusuario"]]["GESTION"][$row["gestion"]]["ESTADO"][$row["estado"]]["CONTEO"] = $row["conteo"];
          $arrHistorialPremora[$row["nombreusuario"]]["GESTION"][$row["gestion"]]["ESTADO"][$row["estado"]]["SALDO"] = $row["saldo"];
        }
      }

      mysqli_close($conn);
      return $arrHistorialPremora;
    }
  }
}

class rpt_hp_view
{

  private $objModel;
  private $arrRolUser;

  public function __construct($arrRolUser)
  {
    $this->objModel = new rpt_hp_model($arrRolUser);
    $this->arrRolUser = $arrRolUser;
  }

  public function drawSelectUsuarios()
  {
    $arrUsuarios = $this->objModel->getUsuarios();
    ?>
    <select id="selectUsuarios" name="selectUsuarios" style="text-align: center;" class="form-control">
      <option value="0">-- Todos los usuarios --</option>
      <?php
      reset($arrUsuarios);
      while ($rTMP = each($arrUsuarios)) {
        $intID =  $rTMP["key"];
        $strNombre = utf8_encode($rTMP["value"]["NOMBRE"]);
      ?>
        <option value="<?php print $intID; ?>"><?php print $strNombre; ?></option>
      <?php
      }
      ?>
    </select>
    <?php
  }

  public function drawContentDetail($arrDetail, $boolExport = false)
  {
    if (count($arrDetail) > 0) {
      $arrTMP = $arrDetail;
      $intTotalRecuentoTMP = 0;
      reset($arrTMP);
      while ($cTMP = each($arrTMP)) {
        $strTMPUsuario = $cTMP["key"];
        $arrTotalConteo[$strTMPUsuario] = 0;
        reset($cTMP["value"]["GESTION"]);
        while ($cTMP2 = each($cTMP["value"]["GESTION"])) {
          reset($cTMP2["value"]["ESTADO"]);
          while ($cTMP3 = each($cTMP2["value"]["ESTADO"])) {
            $intConteo = $cTMP3["value"]["CONTEO"];
            $arrTotalConteo[$strTMPUsuario] = $arrTotalConteo[$strTMPUsuario] + $intConteo;
          }
        }
      }

      $arrTotales["Con gestion"]["Cancelado"] = 0;
      $arrTotales["Con gestion"]["Al dia"] = 0;
      $arrTotales["Con gestion"]["En mora"] = 0;
      $arrTotales["Con gestion"]["Premora"] = 0;
      $arrTotales["Sin gestion"]["Cancelado"] = 0;
      $arrTotales["Sin gestion"]["Al dia"] = 0;
      $arrTotales["Sin gestion"]["En mora"] = 0;
      $arrTotales["Sin gestion"]["Premora"] = 0;

      $arrConteo["Con gestion"]["Cancelado"]["Conteo"] = 0;
      $arrConteo["Con gestion"]["Al dia"]["Conteo"] = 0;
      $arrConteo["Con gestion"]["En mora"]["Conteo"] = 0;
      $arrConteo["Con gestion"]["Premora"]["Conteo"] = 0;
      $arrConteo["Sin gestion"]["Cancelado"]["Conteo"] = 0;
      $arrConteo["Sin gestion"]["Al dia"]["Conteo"] = 0;
      $arrConteo["Sin gestion"]["En mora"]["Conteo"] = 0;
      $arrConteo["Sin gestion"]["Premora"]["Conteo"] = 0;

    ?>
      <table class="table table-sm table-striped">
        <thead>
          <tr>
            <th style="text-align:center;">No.</th>
            <th style="text-align:center;">Usuario</th>
            <th style="text-align:center;">Estadísticas</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $intCount = 0;
          reset($arrDetail);
          while ($rTMP = each($arrDetail)) {
            $intCount++;
            $strUsuario = $rTMP["key"];
          ?>
            <tr>
              <td style="text-align:center; vertical-align:middle;">
                <h3><span class="badge bg-light-blue"><?php print $intCount; ?></span></h3>
              </td>
              <td style="text-align:center; vertical-align:middle;"><?php print $strUsuario; ?></td>
              <td>
                <?php
                reset($rTMP["value"]["GESTION"]);
                while ($rTMP2 = each($rTMP["value"]["GESTION"])) {
                  $strGestion = $rTMP2["key"];
                  $intTotal = 0;
                  $intTotalSaldo = 0;
                ?>
                  <table class="table">
                    <tr>
                      <td style="text-align:center; vertical-align:middle;"><?php print $strGestion; ?></td>
                      <td>
                        <table class="table table-sm table-striped">
                          <thead>
                            <tr>
                              <th>Estado</th>
                              <th>Porcentaje</th>
                              <th>Conteo</th>
                              <th>Saldo</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            reset($rTMP2["value"]["ESTADO"]);
                            while ($rTMP3 = each($rTMP2["value"]["ESTADO"])) {
                              $strEstado = $rTMP3["key"];
                              $strEstado = ($strEstado != '') ? $strEstado : "Cancelado";
                              $intConteo = $rTMP3["value"]["CONTEO"];
                              $decSaldo = $rTMP3["value"]["SALDO"];
                              $intPorcentaje = ($intConteo / $arrTotalConteo[$strUsuario]) * 100;
                              $intPorcentaje = number_format($intPorcentaje, 2);
                              $intTotal = $intTotal + $intConteo;
                              $intTotalSaldo = $intTotalSaldo + $decSaldo;
                              $arrTotales[$strGestion][$strEstado] += $decSaldo;
                              $arrConteo[$strGestion][$strEstado]["Conteo"] += $intConteo;
                            ?>
                              <tr>
                                <td><?php print $strEstado; ?></td>
                                <td><?php print $intPorcentaje . '%'; ?></td>
                                <td><?php print $intConteo; ?></td>
                                <?php
                                if ($boolExport) {
                                ?>
                                  <td><?php print number_format($decSaldo, 2, '.', ','); ?></td>
                                <?php
                                } else {
                                ?>
                                  <td><?php print 'Q.' . number_format($decSaldo, 2, '.', ','); ?></td>
                                <?php
                                }
                                ?>

                              </tr>
                            <?php
                            }
                            ?>
                            <tr>
                              <td style="text-align:center;"><b>Totales..</b></td>
                              <td><b>100%</b></td>
                              <td><b><?php print $intTotal; ?></b></td>
                              <?php
                              if ($boolExport) {
                              ?>
                                <td><b><?php print number_format($intTotalSaldo, 2, '.', ','); ?></b></td>
                              <?php
                              } else {
                              ?>
                                <td><b><?php print 'Q.' . number_format($intTotalSaldo, 2, '.', ','); ?></b></td>
                              <?php
                              }
                              ?>
                            </tr>
                          </tbody>
                        </table>
                      </td>
                    </tr>
                  </table>
                  <br>
                <?php
                }
                ?>
              </td>
            </tr>
          <?php
          }
          ?>
        </tbody>
      </table>
      <br><br>
      <?php
      $sumGestionConteo = $arrConteo["Con gestion"]["Cancelado"]["Conteo"] +
        $arrConteo["Con gestion"]["Al dia"]["Conteo"] +
        $arrConteo["Con gestion"]["En mora"]["Conteo"] +
        $arrConteo["Con gestion"]["Premora"]["Conteo"];

      $sumSGestionConteo = $arrConteo["Sin gestion"]["Cancelado"]["Conteo"] +
        $arrConteo["Sin gestion"]["Al dia"]["Conteo"] +
        $arrConteo["Sin gestion"]["En mora"]["Conteo"] +
        $arrConteo["Sin gestion"]["Premora"]["Conteo"];


      ?>
      <table class="table">
        <tr>
          <td colspan="2" style="background-color: #20b4c5; color:white; text-align:center;">
            <h3>Resumen Historial Premora</h3>
          </td>
        </tr>
        <tr>
          <td style="text-align:center; vertical-align: middle;">Con gestión</td>
          <td>
            <table class="table table-striped">
              <thead>
                <tr>
                  <td><b>Estado</b></td>
                  <td><b>Conteo</b></td>
                  <td><b>Saldo</b></td>
                  <td><b>Porcentaje</b></td>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><b>Cancelado</b></td>
                  <td>
                    <?php
                    print $arrConteo["Con gestion"]["Cancelado"]["Conteo"];
                    ?>
                  </td>
                  <td>
                    <?php
                    if ($boolExport) {
                      print number_format($arrTotales["Con gestion"]["Cancelado"], 2, '.', ',');
                    } else {
                      print 'Q.' . number_format($arrTotales["Con gestion"]["Cancelado"], 2, '.', ',');
                    }
                    ?>
                  </td>
                  <td>
                    <?php
                    $intCC = ($arrConteo["Con gestion"]["Cancelado"]["Conteo"] / $sumGestionConteo) * 100;
                    $intCC = number_format($intCC, 2);
                    print $intCC . '%';
                    ?>
                  </td>
                </tr>
                <tr>
                  <td><b>Al dia</b></td>
                  <td>
                    <?php
                    print $arrConteo["Con gestion"]["Al dia"]["Conteo"];
                    ?>
                  </td>
                  <td>
                    <?php
                    if ($boolExport) {
                      print number_format($arrTotales["Con gestion"]["Al dia"], 2, '.', ',');
                    } else {
                      print 'Q.' . number_format($arrTotales["Con gestion"]["Al dia"], 2, '.', ',');
                    }
                    ?>
                  </td>
                  <td>
                    <?php
                    $intCAL = ($arrConteo["Con gestion"]["Al dia"]["Conteo"] / $sumGestionConteo) * 100;
                    $intCAL = number_format($intCAL, 2);
                    print $intCAL . '%';
                    ?>
                  </td>
                </tr>
                <tr>
                  <td><b>En mora</b></td>
                  <td>
                    <?php
                    print $arrConteo["Con gestion"]["En mora"]["Conteo"];
                    ?>
                  </td>
                  <td>
                    <?php
                    if ($boolExport) {
                      print number_format($arrTotales["Con gestion"]["En mora"], 2, '.', ',');
                    } else {
                      print 'Q.' . number_format($arrTotales["Con gestion"]["En mora"], 2, '.', ',');
                    }
                    ?>
                  </td>
                  <td>
                    <?php
                    $intCEN = ($arrConteo["Con gestion"]["En mora"]["Conteo"] / $sumGestionConteo) * 100;
                    $intCEN = number_format($intCEN, 2);
                    print $intCEN . '%';
                    ?>
                  </td>
                </tr>
                <tr>
                  <td><b>Premora</b></td>
                  <td>
                    <?php
                    print $arrConteo["Con gestion"]["Premora"]["Conteo"];
                    ?>
                  </td>
                  <td>
                    <?php
                    if ($boolExport) {
                      print number_format($arrTotales["Con gestion"]["Premora"], 2, '.', ',');
                    } else {
                      print 'Q.' . number_format($arrTotales["Con gestion"]["Premora"], 2, '.', ',');
                    }
                    ?>
                  </td>
                  <td>
                    <?php
                    $intCPRE = ($arrConteo["Con gestion"]["Premora"]["Conteo"] / $sumGestionConteo) * 100;
                    $intCPRE = number_format($intCPRE, 2);
                    print $intCPRE . '%';
                    ?>
                  </td>
                </tr>
                <tr>
                  <td><b>Total..</b></td>
                  <td>
                    <?php
                    print $sumGestionConteo;
                    ?>
                  </td>
                  <td>
                    <b>
                      <?php
                      $strSumaTotalC = $arrTotales["Con gestion"]["Cancelado"] + $arrTotales["Con gestion"]["Al dia"] + $arrTotales["Con gestion"]["En mora"] + $arrTotales["Con gestion"]["Premora"];
                      if ($boolExport) {
                        print number_format($strSumaTotalC, 2, '.', ',');
                      } else {
                        print 'Q.' . number_format($strSumaTotalC, 2, '.', ',');
                      }
                      ?>
                    </b>
                  </td>
                  <td>100%</td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr>
        <tr>
          <td style="text-align:center; vertical-align: middle;">Sin gestión</td>
          <td>
            <table class="table table-striped">
              <thead>
                <tr>
                  <td><b>Estado</b></td>
                  <td><b>Conteo</b></td>
                  <td><b>Saldo</b></td>
                  <td><b>Porcentaje</b></td>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><b>Cancelado</b></td>
                  <td>
                    <?php
                    print $arrConteo["Sin gestion"]["Cancelado"]["Conteo"];
                    ?>
                  </td>
                  <td>
                    <?php
                    if ($boolExport) {
                      print number_format($arrTotales["Sin gestion"]["Cancelado"], 2, '.', ',');
                    } else {
                      print 'Q.' . number_format($arrTotales["Sin gestion"]["Cancelado"], 2, '.', ',');
                    }
                    ?>
                  </td>
                  <td>
                    <?php
                    $intSC = ($arrConteo["Sin gestion"]["Cancelado"]["Conteo"] / $sumSGestionConteo) * 100;
                    $intSC = number_format($intSC, 2);
                    print $intSC . '%';
                    ?>
                  </td>
                </tr>
                <tr>
                  <td><b>Al dia</b></td>
                  <td>
                    <?php
                    print $arrConteo["Sin gestion"]["Al dia"]["Conteo"];
                    ?>
                  </td>
                  <td>
                    <?php
                    if ($boolExport) {
                      print number_format($arrTotales["Sin gestion"]["Al dia"], 2, '.', ',');
                    } else {
                      print 'Q.' . number_format($arrTotales["Sin gestion"]["Al dia"], 2, '.', ',');
                    }
                    ?>
                  </td>
                  <td>
                    <?php
                    $intSAL = ($arrConteo["Sin gestion"]["Al dia"]["Conteo"] / $sumSGestionConteo) * 100;
                    $intSAL = number_format($intSAL, 2);
                    print $intSAL . '%';
                    ?>
                  </td>
                </tr>
                <tr>
                  <td><b>En mora</b></td>
                  <td>
                    <?php
                    print $arrConteo["Sin gestion"]["En mora"]["Conteo"];
                    ?>
                  </td>
                  <td>
                    <?php
                    if ($boolExport) {
                      print number_format($arrTotales["Sin gestion"]["En mora"], 2, '.', ',');
                    } else {
                      print 'Q.' . number_format($arrTotales["Sin gestion"]["En mora"], 2, '.', ',');
                    }
                    ?>
                  </td>
                  <td>
                    <?php
                    $intSEM = ($arrConteo["Sin gestion"]["En mora"]["Conteo"] / $sumSGestionConteo) * 100;
                    $intSEM = number_format($intSEM, 2);
                    print $intSEM . '%';
                    ?>
                  </td>
                </tr>
                <tr>
                  <td><b>Premora</b></td>
                  <td>
                    <?php
                    print $arrConteo["Sin gestion"]["Premora"]["Conteo"];
                    ?>
                  </td>
                  <td>
                    <?php
                    if ($boolExport) {
                      print number_format($arrTotales["Sin gestion"]["Premora"], 2, '.', ',');
                    } else {
                      print 'Q.' . number_format($arrTotales["Sin gestion"]["Premora"], 2, '.', ',');
                    }
                    ?>
                  </td>
                  <td>
                    <?php
                    $intSPRE = ($arrConteo["Sin gestion"]["Premora"]["Conteo"] / $sumSGestionConteo) * 100;
                    $intSPRE = number_format($intSPRE, 2);
                    print $intSPRE . '%';
                    ?>
                  </td>
                </tr>
                <tr>
                  <td><b>Total:</b></td>
                  <td>
                    <?php
                    print $sumSGestionConteo;
                    ?>
                  </td>
                  <td>
                    <b>
                      <?php
                      $strSumaTotalS = $arrTotales["Sin gestion"]["Cancelado"] + $arrTotales["Sin gestion"]["Al dia"] + $arrTotales["Sin gestion"]["En mora"] + $arrTotales["Sin gestion"]["Premora"];
                      if ($boolExport) {
                        print number_format($strSumaTotalS, 2, '.', ',');
                      } else {
                        print 'Q.' . number_format($strSumaTotalS, 2, '.', ',');
                      }
                      ?>
                    </b>
                  </td>
                  <td>100%</td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr>
      </table>
    <?php

    } else {
    ?>
      <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
          <h3>No se encontraron resultados.</h3>
        </div>
      </div>
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
            <?php draMenu($this->arrRolUser, 'rpt_historial_premora', 3); ?>
          </section>
          <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
          <!-- Content Header (Page header) -->
          <br>
          <section class="content-header">
            <h1>Reporte Historial Premora</h1>
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
                      <div class="col-xs-12 col-sm-9 col-md-4 col-lg-4" style="text-align:center; vertical-align:middle;">
                        <label style="color:black;"><b>Usuario</b></label>
                        <?php $this->drawSelectUsuarios(); ?>
                      </div>  
                      <div class="col-xs-12 col-sm-9 col-md-4 col-lg-4" style="text-align:center; vertical-align:middle;">
                        <label style="color:black;"><b>Mes</b></label>
                        <select class="form-control" id="sltMes" name="sltMes" style="text-align:center;">
                          <option value="01">Enero</option>
                          <option value="02">Febrero</option>
                          <option value="03">Marzo</option>
                          <option value="04">Abril</option>
                          <option value="05">Mayo</option>
                          <option value="06">Junio</option>
                          <option value="07">Julio</option>
                          <option value="08">Agosto</option>
                          <option value="09">Septiembre</option>
                          <option value="10">Octubre</option>
                          <option value="11">Noviembre</option>
                          <option value="12">Diciembre</option>
                        </select>
                      </div>
                      <div class="col-xs-12 col-sm-9 col-md-4 col-lg-4" style="text-align:center; vertical-align:middle;">
                        <label style="color:black;"><b>Año</b></label>
                        <select class="form-control" id="sltAnio" name="sltAnio" style="text-align:center;">
                          <?php
                          $intYearNow = date("Y");
                          for ($i = 2020; $i <= $intYearNow; $i++) {
                          ?>
                            <option value="<?php print $i; ?>"><?php print $i; ?></option>
                          <?php
                          }
                          ?>
                        </select>
                      </div>
                    </div>
                    <br>
                    <div class="row">
                      <div class="col-xs-12 col-sm-9 col-md-4 col-lg-4" style="text-align:center; vertical-align:middle;">
                        <button id="btngenerarreporte" class="btn btn-success btn-raised btn-block" onclick="getDetailReporte()">
                          <i class="fa fa-file-text-o" aria-hidden="true"></i> Generar Reporte
                        </button>
                      </div>
                      <div class="col-xs-12 col-sm-9 col-md-4 col-lg-4" style="text-align:center; vertical-align:middle;">
                        <button class="btn btn-success btn-raised btn-block" id="btnExportarExcel" onclick="fntExportarData('EXCEL')" style="display:none;">
                          <i class="fa fa-file-excel-o" aria-hidden="true"></i> Exportar a Excel
                        </button>
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
              url: "rpt_historial_premora.php",
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


        function getDetailReporte() {
          var boolError = false;
          if (boolError == false) {
            var objSerialized = $("#divFiltros").find("select, input").serialize();
            $.ajax({
              url: "rpt_historial_premora.php",
              data: objSerialized,
              type: "post",
              dataType: "html",
              beforeSend: function() {
                $("#divDetalle").html('');
                $("#divShowLoadingGeneralBig").css("z-index", 1050);
                $("#divShowLoadingGeneralBig").show();
                $("#btngenerarreporte").prop('disabled', true);
                $("#btnExportarExcel").prop('disabled', true);
              },
              success: function(data) {
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
          input.name = key;
          'name-as-seen-at-the-server';
          input.value = value;
          theForm.appendChild(input);
        }

        //Permite enviar la peticion para poder exportar el reporte en PDF o EXCEL
        function fntExportarData(strTipo) {

          var intUsuarios = $("#selectUsuarios").val();
          var sltMes = $('#sltMes').val();
          var sltAnio = $('#sltAnio').val();

          var objForm = document.getElementById("frmFiltros");
          objForm.target = "_self";
          addHidden(objForm, 'hdnExportar', 'true');
          addHidden(objForm, 'TipoExport', strTipo);
          addHidden(objForm, 'intUsuarios', intUsuarios);
          addHidden(objForm, 'sltMes', sltMes);
          addHidden(objForm, 'sltAnio', sltAnio);
          objForm.submit();
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