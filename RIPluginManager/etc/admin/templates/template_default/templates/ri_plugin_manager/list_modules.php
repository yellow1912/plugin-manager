<?php
if(count($modules) > 0){ 
	$installed_modules = $ri_plugin_config->RIPluginManager__installed;
	?>
	<table id="list_modules" class="normal_tables">
		<tr>
		<th>Module(id)</th>
		<th>Installed version</th>
		<th>Available local version</th>
		<th>Available remote version</th>
		<th>Actions</th>
		</tr>
		<?php foreach ($modules as $module):?>
		<tr>
			<td>
				<?php echo $module;?>
			</td>
			<td>
				<?php echo $installed_modules[$module]["version"]?>
			</td>
			<td>
				<?php echo $ri_plugin_config->{"{$module}__info__version"};?>
			</td>
			<td>
			</td>
			<td>
				Manage - Enable - Disable - Uninstal
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
<?php
}
?>