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

namespace KevinDitscheid\AddWizard\Controller\Wizard;

use TYPO3\CMS\Backend\Controller\Wizard\AddController as CoreAddController;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;

/**
 * Replacement class for the add wizard controller of typo3/cms-backend
 *
 * @author Kevin Ditscheid <ditscheid@engine-productions.de>
 */
class AddController extends CoreAddController {

	/**
	 * Main function
	 * Will issue a location-header, redirecting either BACK or to a new FormEngine instance...
	 *
	 * @return void
	 */
	public function main () {
		if ( $this->returnEditConf ) {
			if ( $this->processDataFlag ) {
				// We need the TcaDatabaseRecord DataGroup handler, because the field values could be more complex than just
				// the Data inside the database row. Instantiate TcaDatabaseRecord as $formDataGroup here to fix that:
				// https://forge.typo3.org/issues/76863
				/** @var TcaDatabaseRecord $formDataGroup */
				$formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
				/** @var FormDataCompiler $formDataCompiler */
				$formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
				$input = [
						'tableName' => $this->P['table'],
						'vanillaUid' => (int) $this->P['uid'],
						'command' => 'edit',
				];
				$result = $formDataCompiler->compile($input);
				$currentParentRow = $result['databaseRow'];

				// If that record was found (should absolutely be...), then init DataHandler and set, prepend or append
				// the record
				if ( is_array($currentParentRow) ) {
					/** @var DataHandler $dataHandler */
					$dataHandler = GeneralUtility::makeInstance(DataHandler::class);
					$dataHandler->stripslashes_values = false;
					$data = [ ];
					$recordId = $this->table . '_' . $this->id;
					// Setting the new field data:
					// If the field is a flexForm field, work with the XML structure instead:
					if ( $this->P['flexFormPath'] ) {
						// Current value of flexForm path:
						$currentFlexFormData = GeneralUtility::xml2array($currentParentRow[$this->P['field']]);
						/** @var FlexFormTools $flexFormTools */
						$flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
						$currentFlexFormValue = $flexFormTools->getArrayValueByPath(
							$this->P['flexFormPath'], $currentFlexFormData
						);
						$insertValue = '';
						switch ( (string) $this->P['params']['setValue'] ) {
							case 'set':
								$insertValue = $recordId;
								break;
							case 'prepend':
								$insertValue = $currentFlexFormValue . ',' . $recordId;
								break;
							case 'append':
								$insertValue = $recordId . ',' . $currentFlexFormValue;
								break;
						}
						$insertValue = implode(',', GeneralUtility::trimExplode(',', $insertValue, true));
						$data[$this->P['table']][$this->P['uid']][$this->P['field']] = [ ];
						$flexFormTools->setArrayValueByPath(
							$this->P['flexFormPath'], $data[$this->P['table']][$this->P['uid']][$this->P['field']], $insertValue
						);
					} else {
						// we need to check the row for its data type. If it is an array, it will probably store the relations to
						// other records. We need to implode this into a comma separated list to be able to restore the stored
						// values after the wizard falls back to the parent record
						// @TODO: find out how to detect the table prefix of the records, already stored in the field
						$currentValue = $currentParentRow[$this->P['field']];
						if ( is_array($currentValue) ) {
							$currentValue = implode(',', $currentValue);
						}
						switch ( (string) $this->P['params']['setValue'] ) {
							case 'set':
								$data[$this->P['table']][$this->P['uid']][$this->P['field']] = $recordId;
								break;
							case 'prepend':
								$data[$this->P['table']][$this->P['uid']][$this->P['field']] = $currentValue . ',' . $recordId;
								break;
							case 'append':
								$data[$this->P['table']][$this->P['uid']][$this->P['field']] = $recordId . ',' . $currentValue;
								break;
						}
						$data[$this->P['table']][$this->P['uid']][$this->P['field']] = implode(
							',', GeneralUtility::trimExplode(
								',', $data[$this->P['table']][$this->P['uid']][$this->P['field']], true
							)
						);
					}
					// Submit the data:
					$dataHandler->start($data, [ ]);
					$dataHandler->process_datamap();
				}
			}
			// Return to the parent FormEngine record editing session:
			HttpUtility::redirect(GeneralUtility::sanitizeLocalUrl($this->P['returnUrl']));
		} else {
			// Redirecting to FormEngine with instructions to create a new record
			// AND when closing to return back with information about that records ID etc.
			$redirectUrl = BackendUtility::getModuleUrl('record_edit',
					[
						'returnEditConf' => 1,
						'edit[' . $this->P['params']['table'] . '][' . $this->pid . ']' => 'new',
						'returnUrl' => GeneralUtility::removeXSS(GeneralUtility::getIndpEnv('REQUEST_URI'))
			]);
			HttpUtility::redirect($redirectUrl);
		}
	}

}
