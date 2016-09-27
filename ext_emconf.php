<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

$EM_CONF[$_EXTKEY] = array(
		'title' => 'Add Wizard',
		'description' => 'This extension fixes a bug with the add-wizard and mm-realations',
		'category' => 'Misc',
		'author' => 'Kevin Ditscheid',
		'author_email' => 'kevinditscheid@gmail.com',
		'author_company' => '',
		'state' => 'alpha',
		'internal' => '',
		'uploadfolder' => '0',
		'createDirs' => '',
		'clearCacheOnLoad' => 0,
		'version' => '0.0.1',
		'constraints' => array(
				'depends' => array(
						'typo3' => '7.6'
				),
				'conflicts' => array(
				),
				'suggests' => array(
				),
		),
);
