<?php if(!defined('PLX_ROOT')) exit; 

if (isset($_GET['rec']) && $_GET['rec'] == true) {
	$plxPlugin->archiveLogs();
}
if (isset($_GET['purge']) && $_GET['purge'] == true) {
	$plxPlugin->purgeLogs();
}
if (isset($_GET['history']) && $_GET['history'] == true) {
	$plxPlugin->showHistory();
	
} else {
?>
<style type="text/css">
	pre {
		border: 1px solid #b6b1b3;
		padding:10px;
		font-size:1.2em;
		background: #caffbc;
	}
	.failed {
		background: #fff489;
	}
</style>
<ul>
	<li>
		<a href="<?php echo PLX_CORE ?>admin/plugin.php?p=logauth&rec=true">Archiver les logs</a>
	</li>
	<li>
		<a href="<?php echo PLX_CORE ?>admin/plugin.php?p=logauth&history=true">Historique des logs</a>
	</li>
	<li>
		----------------------------------------
	</li>
	<li>
		<a href="<?php echo PLX_CORE ?>admin/plugin.php?p=logauth&purge=true" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ces archives ? Cette action est irréversible !');">Purger l'historique des logs</a>
	</li>
</ul>
<h1>Fichier de log</h1>
<?php }?>