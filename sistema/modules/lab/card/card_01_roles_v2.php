<?php
/**
 * CARD 01 - Acceso requerido (ACL + Permisos) por análisis del archivo
 * Versión: v2.0
 * Archivo: /modules/lab/card/card_01_roles_v2.php
 * ID interno: LAB-CARD-01
 */
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('lab01_skip_ws')) {
    function lab01_skip_ws(array $tokens, int &$i): void {
        $n = count($tokens);
        while ($i < $n) {
            $t = $tokens[$i];
            if (is_array($t)) {
                $id = $t[0];
                if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) { $i++; continue; }
            }
            break;
        }
    }
}

if (!function_exists('lab01_parse_array_literal')) {
    function lab01_parse_array_literal(array $tokens, int &$i): ?array {
        $n = count($tokens);
        lab01_skip_ws($tokens, $i);
        if ($i >= $n) return null;
        $t = $tokens[$i];

        if (is_array($t) && $t[0] === T_ARRAY) {
            $i++;
            lab01_skip_ws($tokens, $i);
            if ($i >= $n || $tokens[$i] !== '(') return null;
            $i++;
            $endChar = ')';
        } elseif ($t === '[') {
            $i++;
            $endChar = ']';
        } else {
            return null;
        }

        $values = [];
        $depth = 1;
        while ($i < $n && $depth > 0) {
            $t = $tokens[$i];

            if ($t === '(' || $t === '[') { $depth++; $i++; continue; }
            if ($t === ')' || $t === ']') { $depth--; $i++; continue; }

            if (is_array($t)) {
                if ($t[0] === T_LNUMBER) {
                    $values[] = (int)$t[1];
                } elseif ($t[0] === T_CONSTANT_ENCAPSED_STRING) {
                    $s = $t[1];
                    if (strlen($s) >= 2 && ($s[0] === "'" || $s[0] === '"')) $s = substr($s, 1, -1);
                    $values[] = $s;
                }
            }
            $i++;
        }
        return $values;
    }
}

if (!function_exists('lab01_parse_access_from_file')) {
    function lab01_parse_access_from_file(string $phpCode): array {
        $tokens = token_get_all($phpCode);
        $n = count($tokens);

        $varArrays = [];
        for ($i = 0; $i < $n; $i++) {
            $t = $tokens[$i];
            if (is_array($t) && $t[0] === T_VARIABLE) {
                $varName = $t[1];
                $j = $i + 1;
                lab01_skip_ws($tokens, $j);
                if ($j < $n && $tokens[$j] === '=') {
                    $j++;
                    $k = $j;
                    $arr = lab01_parse_array_literal($tokens, $k);
                    if (is_array($arr)) $varArrays[$varName] = $arr;
                }
            }
        }

        $out = ['acl_ids'=>null,'permisos'=>null,'acl_line'=>null,'perm_line'=>null];

        for ($i = 0; $i < $n; $i++) {
            $t = $tokens[$i];
            if (!is_array($t) || $t[0] !== T_STRING) continue;

            $fn = $t[1];
            if ($fn !== 'acl_require_ids' && $fn !== 'verificarPermiso') continue;

            $line = $t[2] ?? null;
            $j = $i + 1;
            lab01_skip_ws($tokens, $j);
            if ($j >= $n || $tokens[$j] !== '(') continue;
            $j++;
            lab01_skip_ws($tokens, $j);

            $argVals = null;
            $k = $j;
            $arr = lab01_parse_array_literal($tokens, $k);
            if (is_array($arr)) {
                $argVals = $arr;
            } else {
                $tok = $tokens[$j] ?? null;
                if (is_array($tok) && $tok[0] === T_VARIABLE) {
                    $v = $tok[1];
                    if (isset($varArrays[$v])) $argVals = $varArrays[$v];
                }
            }

            if ($fn === 'acl_require_ids' && $out['acl_ids'] === null) {
                $out['acl_ids'] = $argVals; $out['acl_line'] = $line;
            }
            if ($fn === 'verificarPermiso' && $out['permisos'] === null) {
                $out['permisos'] = $argVals; $out['perm_line'] = $line;
            }
        }

        return $out;
    }
}

// Root proyecto
$projectRoot = realpath(__DIR__ . '/../../../..');

$target = $_GET['target'] ?? '';
if ($target === '') $target = $_SERVER['SCRIPT_FILENAME'] ?? '';
if ($target && $projectRoot && $target[0] !== '/') $target = $projectRoot . '/' . ltrim($target, '/');

$real = $target ? realpath($target) : false;
$allowed = false;
if ($real && $projectRoot && strncmp($real, $projectRoot, strlen($projectRoot)) === 0 && is_file($real)) $allowed = true;

$info = null; $error = null;
if (!$allowed) {
    $error = 'No se pudo analizar el archivo (ruta no válida o fuera del proyecto).';
} else {
    $code = @file_get_contents($real);
    if ($code === false) $error = 'No se pudo leer el archivo.';
    else $info = lab01_parse_access_from_file($code);
}

$relPath = ($allowed && $projectRoot) ? str_replace($projectRoot, '', $real) : ($real ?: '—');
?>

<div class="card card-outline card-primary shadow-sm" data-card="01" data-version="2.0" data-card-id="LAB-CARD-01">
  <div class="card-header">
    <h3 class="card-title"><strong>[Card 01 v2.0]</strong> Acceso requerido (detectado)</h3>
    <div class="card-tools">
      <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
    </div>
  </div>

  <div class="card-body">
    <div class="mb-2">
      <div class="small text-muted">Archivo analizado</div>
      <code><?= h($relPath ?: '—') ?></code>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-warning mb-0"><?= h($error) ?></div>
    <?php else: ?>
      <div class="row">
        <div class="col-md-6">
          <div class="small text-muted mb-1">ACL (IDs permitidos)</div>
          <?php if (!empty($info['acl_ids'])): ?>
            <code>[<?= h(implode(', ', array_map('intval', (array)$info['acl_ids']))) ?>]</code>
            <?php if ($info['acl_line']): ?><div class="small text-muted mt-1">Línea: <?= (int)$info['acl_line'] ?></div><?php endif; ?>
          <?php else: ?>
            <span class="text-warning">No detectado</span>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <div class="small text-muted mb-1">Permisos requeridos</div>
          <?php if (!empty($info['permisos'])): ?>
            <code><?= h(implode(' | ', (array)$info['permisos'])) ?></code>
            <?php if ($info['perm_line']): ?><div class="small text-muted mt-1">Línea: <?= (int)$info['perm_line'] ?></div><?php endif; ?>
          <?php else: ?>
            <span class="text-warning">No detectado</span>
          <?php endif; ?>
        </div>
      </div>

      <hr>
      <div class="small text-muted">
        Si quieres analizar otra página desde Lab:
        <code>?target=/modules/otro_modulo/index.php</code>
      </div>
    <?php endif; ?>
  </div>

  <div class="card-footer small text-muted">LAB-CARD-01 · v2.0 · <?= h(basename(__FILE__)) ?></div>
</div>
