<?php
/**
 * Visor PDF unificado (vista previa sin timbrar y comprobantes timbrados).
 *
 * @var \App\View\AppView $this
 * @var string $pdfSrc      URL del PDF (verPdf o tmpPdf)
 * @var string $pageTitle   Título de la pestaña
 * @var bool $isPreview     true = documento sin timbre fiscal
 */
$isPreview = $isPreview ?? false;
$pageTitle = $pageTitle ?? 'CFDI';
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<title><?= h($pageTitle) ?></title>
	<style>
		html, body { margin: 0; height: 100%; overflow: hidden; background: #3d3d3d; font-family: system-ui, sans-serif; }
		.pdf-viewer-bar {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			z-index: 2;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			padding: 8px 14px;
			background: #1a1a1a;
			color: #e8e8e8;
			font-size: 13px;
			box-shadow: 0 1px 0 rgba(255,255,255,.08);
		}
		.pdf-viewer-bar__title { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.pdf-viewer-badge {
			flex-shrink: 0;
			font-size: 11px;
			font-weight: 600;
			padding: 3px 8px;
			border-radius: 4px;
			background: #2BBFB3;
			color: #0d2d2a;
		}
		.pdf-viewer-badge--preview { background: #f59e0b; color: #422006; }
		.pdf-viewer-frame-wrap {
			position: fixed;
			top: 40px;
			left: 0;
			right: 0;
			bottom: 0;
		}
		.pdf-viewer-frame-wrap iframe,
		.pdf-viewer-frame-wrap object,
		.pdf-viewer-frame-wrap embed {
			width: 100%;
			height: 100%;
			border: 0;
			display: block;
		}
		.pdf-viewer-fallback {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			height: 100%;
			color: #ccc;
			gap: 12px;
			text-align: center;
			padding: 24px;
		}
		.pdf-viewer-fallback a { color: #2BBFB3; font-size: 15px; }
	</style>
</head>
<body>
	<div class="pdf-viewer-bar">
		<span class="pdf-viewer-bar__title"><?= h($pageTitle) ?></span>
		<?php if ($isPreview) : ?>
			<span class="pdf-viewer-badge pdf-viewer-badge--preview">Vista previa — sin timbre</span>
		<?php else : ?>
			<span class="pdf-viewer-badge">CFDI timbrado</span>
		<?php endif; ?>
	</div>
	<div class="pdf-viewer-frame-wrap">
		<iframe src="<?= h($pdfSrc) ?>#toolbar=1&navpanes=0" title="<?= h($pageTitle) ?>">
			<div class="pdf-viewer-fallback">
				<p>Tu navegador no puede mostrar el PDF directamente.</p>
				<a href="<?= h($pdfSrc) ?>" target="_blank" rel="noopener noreferrer">Abrir PDF en nueva pestaña</a>
			</div>
		</iframe>
	</div>
</body>
</html>
