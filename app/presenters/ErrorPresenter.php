<?php

class ErrorPresenter extends BasePresenter
{

	protected function startup()
	{
		parent::startup();

		echo 'GTFO';
		$this->terminate();
	}

}
