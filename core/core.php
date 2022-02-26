<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

/* Funcion que permite establecer conexion con servidor y la base de datos */
function getConexion()
{

    $servername = "localhost:3308";
    $username = "root";
    $password = "";
    $dbname = "premoraagencias";

    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    } else {
        return $conn;
    }
}

/* Funcion que permite realizar el proceso de autentificacion del usuario a la aplicacion */
function auth_user($username, $password)
{
    $conn = getConexion();
    $arrValues = array();

    if ($username != '') {
        $strQuery = "SELECT password FROM usuarios WHERE nombre = '{$username}' AND activo = 1";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrValues["PASSWORD"] = $row["password"];
            }
        }
    }

    if (isset($arrValues["PASSWORD"])) {
        if (($arrValues["PASSWORD"] == $password)) {
            session_start();
            $_SESSION['user_id'] = $username;
            $strValueSession = $_SESSION['user_id'];
            insertSession($strValueSession);
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/* Funcion quer permite convertir una fecha a formato MYSQL */
function convertDateMysql($strFecha)
{
    $strFechaConvert = "";
    if ($strFecha != '') {
        $arrExplode = explode("/", $strFecha);
        $strFechaConvert = $arrExplode[2] . '-' . $arrExplode[1] . '-' . $arrExplode[0];
    }
    return $strFechaConvert;
}

/* Funcion que permite registrar el usuario y fecha en que se creo la sesion */
function insertSession($strSession)
{
    if ($strSession != '') {
        $conn = getConexion();
        $strQuery = "INSERT INTO session_user (nombre, add_user, add_fecha) VALUES ('{$strSession}', 1, now())";
        mysqli_query($conn, $strQuery);
    }
}

/* Funcion quer permite obtener el rol del usuario logeado */
function getRolUserSession($sessionName)
{
    $strRolUserSession = "";
    if ($sessionName != '') {
        $conn = getConexion();
        $strQuery = "SELECT DISTINCT tipo_usuario.nombre
                       FROM usuarios 
                            INNER JOIN session_user 
                                    ON session_user.nombre = usuarios.nombre 
                            INNER JOIN tipo_usuario 
                                    ON usuarios.tipo = tipo_usuario.id
                      WHERE session_user.nombre = '{$sessionName}'";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $strRolUserSession = $row["nombre"];
            }
        }
    }

    return $strRolUserSession;
}

/* Funcion que permite obtener el id del usuario logeado */
function getIDUserSession($sessionName)
{
    $intIDUserSession = "";
    if ($sessionName != '') {
        $conn = getConexion();
        $strQuery = "SELECT usuarios.id
                       FROM usuarios 
                            INNER JOIN session_user 
                                    ON session_user.nombre = usuarios.nombre 
                      WHERE session_user.nombre = '{$sessionName}'";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $intIDUserSession = $row["id"];
            }
        }
    }

    return $intIDUserSession;
}

/* Funcion que permite obtener el nombre del colaborador que esta logeado en usuario */
function getNombreUserSession($sessionName)
{
    $strNameUserSession = "";
    if ($sessionName != '') {
        $conn = getConexion();
        $strQuery = "SELECT usuarios.nombrecolaborador
                       FROM usuarios 
                            INNER JOIN session_user 
                                    ON session_user.nombre = usuarios.nombre 
                      WHERE session_user.nombre = '{$sessionName}'";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $strNameUserSession = $row["nombrecolaborador"];
            }
        }
    }

    return $strNameUserSession;
}

/* Funcion que permite generar un password aleatorio segun el numero de caracteres como parametro */
function generatePassword($length = 8)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $count = mb_strlen($chars);
    for ($i = 0, $result = ''; $i < $length; $i++) {
        $index = rand(0, $count - 1);
        $result .= mb_substr($chars, $index, 1);
    }
    return $result;
}

/* Funcion que permite convertir reemplazar tildes en mayusculas */
function upper_tildes($strString, $boolProper = false)
{
    if ($boolProper) {
        $strString = ucwords($strString);
    } else {
        $strString = strtoupper($strString);
        $strString = str_replace("á", "Á", $strString);
        $strString = str_replace("é", "É", $strString);
        $strString = str_replace("í", "Í", $strString);
        $strString = str_replace("ó", "Ó", $strString);
        $strString = str_replace("ú", "Ú", $strString);
        $strString = str_replace("ä", "Ä", $strString);
        $strString = str_replace("ë", "Ë", $strString);
        $strString = str_replace("ï", "Ï", $strString);
        $strString = str_replace("ö", "Ö", $strString);
        $strString = str_replace("ü", "Ü", $strString);
        $strString = str_replace("ñ", "Ñ", $strString);
    }

    return $strString;
}

/* Funcion que permite filtrar caracterers especiales y tildes en un query */
function getFilterQuery($strFieldsSearch, $strFilterText, $boolAddAnd = true, $boolSepararPorEspacios = true)
{

    $strSearchString = "";
    $strFilterText = upper_tildes(trim($strFilterText));
    $strFilterText = str_replace(array("Á", "É", "Í", "Ó", "Ú"), array("A", "E", "I", "O", "U"), $strFilterText);
    $mixedFieldsSearch = explode(",", $strFieldsSearch);

    if (count($mixedFieldsSearch) > 1) {

        if ($boolSepararPorEspacios) {
            $arrFilterText = explode(" ", $strFilterText);
        } else {
            $arrFilterText[] = $strFilterText;
        }

        while ($arrTMP = each($arrFilterText)) {

            $strSearchString .= (empty($strSearchString)) ? "" : " AND ";
            $strSearchString .= " ( ";

            $intContador = 0;
            reset($mixedFieldsSearch);
            while ($arrFields = each($mixedFieldsSearch)) {
                $strWord = db_escape($arrTMP["value"]);
                $intContador++;
                if ($intContador > 1) $strSearchString .= " OR ";
                $strSearchString .= " UPPER(replace({$arrFields["value"]}, 'áéíóúÁÉÍÓÚ', 'aeiouAEIOU')) LIKE '%{$strWord}%' ";
            }

            $strSearchString .= " ) ";
        }
    } else {
        $strSearchString .= " UPPER(replace({$strFieldsSearch}, 'áéíóúÁÉÍÓÚ', 'aeiouAEIOU')) LIKE '%{$strFilterText}%' ";
    }

    if ($boolAddAnd) {
        $strSearchString = " AND " . $strSearchString;
    }

    return $strSearchString;
}


/* Funcion que permite obtener el conteo de promesas segun el ID del prestamo */
function getCountPromesasPago($intPrestamo)
{
    if ($intPrestamo > 0) {
        $conn = getConexion();
        $intConteo = 0;
        $strQuery = "SELECT COUNT(id) conteo FROM promesa_pago WHERE prestamo = {$intPrestamo}";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $intConteo = $row["conteo"];
            }
        }

        mysqli_close($conn);
        return $intConteo;
    }
}

function getPrestamosRegistrados()
{
    $conn = getConexion();
    $arrPrestamosRegis = array();
    $strQuery = "SELECT numero FROM prestamo ORDER BY numero";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $arrPrestamosRegis[$row["numero"]] = $row["numero"];
        }
    }
    mysqli_close($conn);
}

function getEmailAdministradores()
{
    $conn = getConexion();
    $arrEmailAdmin = array();
    $strQuery = "SELECT email FROM usuarios WHERE tipo = 1 AND activo = 1";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $arrEmailAdmin[$row["email"]] = $row["email"];
        }
    }
    mysqli_close($conn);
    return $arrEmailAdmin;
}

function getSubcategoriasGestiones()
{
    $conn = getConexion();
    $arrSubCategorias = array();
    $strQuery = "SELECT categorias_gestiones.nombre nombre_categoria_gestion, 
                        subcategorias_gestiones.id,
                        subcategorias_gestiones.nombre nombre_sub_categoria
                   FROM subcategorias_gestiones 
                        INNER JOIN categorias_gestiones 
                                ON subcategorias_gestiones.categoria_gestion = categorias_gestiones.id
                  ORDER BY categorias_gestiones.nombre, subcategorias_gestiones.nombre";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $arrSubCategorias[$row["nombre_categoria_gestion"]]["DETAIL"][$row["id"]]["NOMBRE"] = $row["nombre_sub_categoria"];
        }
    }
    mysqli_close($conn);
    return $arrSubCategorias;
}

function getAgenciaByUsuario($intUsuario)
{
    if ($intUsuario > 0) {
        $conn = getConexion();
        $arrAgencias = array();
        $strAgencias = "";
        $strQuery = "SELECT agencia 
                       FROM usuario_agencias
                      WHERE usuario_agencias.usuario = {$intUsuario}";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $arrAgencias[$row["agencia"]] = $row["agencia"];
            }
        }
        mysqli_close($conn);

        $intCountAgencia = count($arrAgencias);
        $intCount = 0;
        $strAgencias = "";
        reset($arrAgencias);
        while ($rTMP = each($arrAgencias)) {
            $intCount++;
            $intAgencia = $rTMP["key"];
            if ($intCount == $intCountAgencia) {
                $strAgencias = $strAgencias . $intAgencia;
            } else {
                $strAgencias = $strAgencias . $intAgencia . ",";
            }
        }

        return $strAgencias;
    }
}

/* Funcion que permite obtener la fecha de ultima actualizacion de la carga */
function getDateUpdateData()
{
    $conn = getConexion();
    $strFecha = "";
    $strQuery = "SELECT DISTINCT DATE_FORMAT(add_fecha, '%d/%m/%Y') add_fecha FROM prestamo ORDER BY add_fecha DESC";
    $result = mysqli_query($conn, $strQuery);
    if (!empty($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $strFecha = $row["add_fecha"];
        }
    }

    mysqli_close($conn);
    return $strFecha;
}

function getCifByPrestamo($intPrestamo)
{
    if ($intPrestamo > 0) {
        $conn = getConexion();
        $strCIF = "";
        $strQuery = "SELECT DISTINCT asociado.cif 
                       FROM prestamo
                            INNER JOIN asociado 
                                    ON prestamo.asociado = asociado.id
                      WHERE prestamo.id = {$intPrestamo}";
        $result = mysqli_query($conn, $strQuery);
        if (!empty($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $strCIF = $row["cif"];
            }
        }

        mysqli_close($conn);
        return $strCIF;
    }
}

/* Funcion que permite dibujar el menu principal de aplicacion */
function draMenu($arrRolUser = "", $strName = '', $intNivel = 0)
{
?>
    <!-- Menu -->
    <ul class="sidebar-menu" data-widget="tree">
        <li class="header"></li>
        <?php

        if (isset($arrRolUser["MASTER"]) &&  $arrRolUser["MASTER"] == true) {
        ?>
            <!-- ADMINISTRADOR -->
            <li class="<?php print ($intNivel == 2) ? 'treeview active menu-open' : 'treeview'; ?>">
                <a href="#">
                    <i class="fa fa-cog"></i> <span>Administrador</span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li class="<?php print ($strName == 'cargadiaria') ? 'active' : ''; ?>"><a href="carga_diaria.php"><i class="fa fa-circle-o"></i>Carga Diaria</a></li>
                    <li class="<?php print ($strName == 'cargalistadocondiciones') ? 'active' : ''; ?>"><a href="carga_listado_condiciones.php"><i class="fa fa-circle-o"></i>Carga Listado de Condiciones</a></li>
                    <li class="<?php print ($strName == 'cat_agencias') ? 'active' : ''; ?>"><a href="cat_agencias.php"><i class="fa fa-circle-o"></i>Catálogo Agencias</a></li>
                    <li class="<?php print ($strName == 'cat_tipo_gestion_cobro') ? 'active' : ''; ?>"><a href="cat_tipo_gestion_cobro.php"><i class="fa fa-circle-o"></i>Catálogo Tipo Gestión</a></li>
                    <li class="<?php print ($strName == 'cat_tipo_riesgo') ? 'active' : ''; ?>"><a href="cat_tipo_riesgo.php"><i class="fa fa-circle-o"></i>Catálogo Tipo de Riesgo</a></li>
                    <li class="<?php print ($strName == 'cat_usuarios') ? 'active' : ''; ?>"><a href="cat_usuarios.php"><i class="fa fa-circle-o"></i>Catálogo Usuarios</a></li>
                    <li class="<?php print ($strName == 'validar_estado') ? 'active' : ''; ?>"><a href="validador_estado.php"><i class="fa fa-circle-o"></i>Validador de Estado</a></li>
                </ul>
            </li>
            <!-- ADMINISTRADOR -->
        <?php
        }
        if ((isset($arrRolUser["MASTER"]) &&  $arrRolUser["MASTER"] == true) || (isset($arrRolUser["NORMAL"]) &&  $arrRolUser["NORMAL"] == true) ||  (isset($arrRolUser["MORA"]) &&  $arrRolUser["MORA"] == true)) {
        ?>
            <!-- MENU PRINCIPAL -->
            <li class="<?php print ($intNivel == 1) ? 'treeview active menu-open' : 'treeview'; ?>">
                <a href="#">
                    <i class="fa fa-bars"></i> <span>Menu Principal</span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li class="<?php print ($strName == 'listado_riesgo_alto') ? 'active' : ''; ?>"><a href="listado_riesgo_alto.php"><i class="fa fa-circle-o"></i>Listado Riesgo Alto</a></li>
                    <li class="<?php print ($strName == 'listado_riesgo_bajo') ? 'active' : ''; ?>"><a href="listado_riesgo_bajo.php"><i class="fa fa-circle-o"></i>Listado Riesgo Bajo</a></li>
                    <li class="<?php print ($strName == 'listado_riesgo_medio') ? 'active' : ''; ?>"><a href="listado_riesgo_medio.php"><i class="fa fa-circle-o"></i>Listado Riesgo Medio</a></li>

                </ul>
            </li>
            <!-- FIN MENU PRINCIPAL-->
        <?php
        }
        if (isset($arrRolUser["MASTER"]) &&  $arrRolUser["MASTER"] == true) {
        ?>
            <!-- REPORTERIA -->
            <li class="<?php print ($intNivel == 3) ? 'treeview active menu-open' : 'treeview'; ?>">
                <a href="#">
                    <i class="fa fa-file-text"></i> <span>Reporteria</span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li class="<?php print ($strName == 'rpt_gestiones') ? 'active' : ''; ?>"><a href="rpt_gestiones.php"><i class="fa fa-circle-o"></i>Reporte Gestiones de Pago</a></li>
                    <li class="<?php print ($strName == 'rpt_historial_premora') ? 'active' : ''; ?>"><a href="rpt_historial_premora.php"><i class="fa fa-circle-o"></i>Reporte Historial Premora</a></li>
                </ul>
            </li>
            <!-- REPORTERIA -->
        <?php
        }
        ?>
    </ul>
<?php
}
?>