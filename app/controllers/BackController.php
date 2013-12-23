<?php

class BackController extends BaseController {
	/**
     * The layout that should be used for responses.
     */
    protected $layout = 'backend';

    /**
    * 
    */
    protected $form = array(
    	'model' 		=> '',
    	'module' 		=> '',
    	'form' 			=> '',
    	'modelName'		=> ''
    );

	/**
	 * Constructor call
	 */
	public function __construct()
	{
		parent::__construct();

		// Enable auto CSRF protection
		$this->beforeFilter('csrf', array('on' => 'post'));

		if ($this->form['module'] == '') $this->form['module'] = str_plural($this->form['model']); 
		if ($this->form['form'] == '') $this->form['form'] = 'form';
		if ($this->form['modelName'] == '') $this->form['modelName'] = 'App\Modules\\'.$this->form['module'].'\Models\\'.$this->form['model'];

	}

	/**
	 * Buils an index form (page) from a model and $data
	 * @param  array $data Array with information how to build the form. See $defaults for details.
	 */
	protected function buildIndexForm($data)
	{
		/*
		 * Default values
		 */
		$defaults = array(
			'buttons'		=> ['new'],
			'search'		=> '',
			'searchFor'		=> 'title',
			'tableHead'		=> array(),
			'tableRow'		=> array(),
			'actions'		=> ['edit', 'delete'],
			'brightenFirst' => true,
			'order'			=> 'id',
			'orderType' 	=> 'desc',
			);

		$data = array_merge($defaults, $data);

		/*
		 * Generate Buttons
		 */
		$buttons = '';
		if (is_array($data['buttons'])) {
			foreach ($data['buttons'] as $type) {
				$type = strtolower($type);
				switch ($type) {
					case 'new':
						$buttons .= button(t('Create new'), route('admin.'.strtolower($this->form['module']).'.create'));
						break;
				}
			}
		}

		/*
		 * Get search string.
		 */
		if (Input::old('search')) {
			$data['search'] = Input::old('search');
		}
		if (Input::get('search')) {
			$data['search'] = Input::get('search');
		}

		/*
		 * Get order attributes.
		 */
		if (Input::get('order')) {
			$order = strtolower(Input::get('order'));
			if (in_array($order, $data['tableHead'])) $data['order'] = $order;

			$orderType = strtolower(Input::get('ordertype'));
			if ($orderType === 'desc' or $orderType === 'asc') {
				$data['orderType'] = $orderType;
			}
		}
		$orderSwitcher = order_switcher($data['order'], $data['orderType'], $data['search']);

		/*
		 * Retrieve model and entity from DB
		 */
		$model = $this->form['modelName'];
		$perPage = Config::get('app.backendItemsPerPage');
		if ($data['search'] and $data['searchFor']) {
			$entities = $model::orderBy($data['order'], $data['orderType'])
			->where($data['searchFor'], 'LIKE', '%'.$data['search'].'%')
			->paginate($perPage);
		} else {
			$entities = $model::orderBy($data['order'], $data['orderType'])
			->paginate($perPage);	
		}

		$paginator = $entities->appends(['order' => $data['order'], 'orderType' => $data['orderType'], 'search' => $data['search']])->links();

		/*
		 * Prepare the table (head and rows)
		 */
		$tableHead = array();
		foreach ($data['tableHead'] as $title => $order) {
			if ($order != NULL) {
				$tableHead[] = HTML::link(URL::current().'?order='.$order, $title);
			} else {
				$tableHead[] = $title;
			}
		}
		if (sizeof($data['actions']) > 0) {
			$tableHead[] = t('Actions');
		}

		$tableRows = array();
		foreach ($entities as $entity) {
			$row = $data['tableRow']($entity);

			if (is_array($data['actions']) and sizeof($data['actions']) > 0) {
				$actionsCode = '';
				foreach ($data['actions'] as $action) {
					if (is_string($action)) {
						$action = strtolower($action);
						switch ($action) {
							case 'edit':
								$actionsCode .= image_link('page_edit', 
									t('Edit'), 
									route('admin.'.strtolower($this->form['module']).'.edit', [$entity->id]));
								break;
							case 'delete':
								$actionsCode .= image_link('bin', 
									t('Delete'), 
									route('admin.'.strtolower($this->form['module']).'.destroy', [$entity->id]).'?method=DELETE');
								break;
						}
						$actionsCode .= ' ';
					}
				}
				$row[] = $actionsCode;
			}
			if (is_callable($data['actions'])) {
				$row[] = $data['actions']($entity);
			}

			$tableRows[] = $row;
		}

		/*
		 * Generate the table
		 */
		$contentTable = $this->contentTable($tableHead, $tableRows, $data['brightenFirst']);

		/*
		 * Generate the view
		 */
		$this->pageView('index_form', array(
			'buttons'		=> $buttons,
			'contentTable' 	=> $contentTable,
			'orderSwitcher' => $orderSwitcher,
			'paginator' 	=> $paginator,
			'searchString'	=> $data['search']
			));
	}

	public function create()
	{
		$this->pageView(strtolower($this->form['module']).'::'.$this->form['form']);
	}

	public function store()
	{
		$entity = new $this->form['modelName'](Input::all());

		$entity->save();

		if (Input::hasFile('image')) {
        	$file = Input::file('image');
    		$extension = $file->getClientOriginalExtension();
	        $fileName = $entity->id.'.'.$extension;
        	$uploaded = $file->move(public_path().'/uploads/'.$this->form['module'], $fileName);
        }

		$this->messageFlash($this->form['model'].t(' created.'));
		return Redirect::route('admin.'.strtolower($this->form['module']).'.index');
	}

	public function edit($id)
	{
		$model = $this->form['modelName'];
		$entity = $model::findOrFail($id);

		$this->pageView(
			strtolower($this->form['module']).'::'.$this->form['form'], 
			array('entity' => $entity)
		);
	}

	public function update($id)
	{
		$model = $this->form['modelName'];
		$entity = $model::findOrFail($id);

		$entity->fill(Input::all());
		$entity->save();

		if (Input::hasFile('image')) {
        	$file = Input::file('image');
    		$extension = $file->getClientOriginalExtension();
	        $fileName = $entity->id.'.'.$extension;
        	$uploaded = $file->move(public_path().'/uploads/'.$this->form['module'], $fileName);
        }		

		return Redirect::route('admin.'.strtolower($this->form['module']).'.index');
	}

	public function destroy($id)
	{
		$model = $this->form['modelName'];
		$model::destroy($id);

		return Redirect::route('admin.'.strtolower($this->form['module']).'.index');
	}

	public function search()
	{
		return Redirect::route('admin.'.strtolower($this->form['module']).'.index')->withInput(Input::only('search'));
	}

	/**
	 * Returns HTML code for a table.
	 * 
	 * @param array     $header 		 Array with the table header items (String-Array)
	 * @param array 	$rows 			 Array with all the table rows items (Array containing String-Arrays)
	 * @param bool 		$highlightFirst	 Enable special look for the items in the first column? (true/false)
	 * @return string
	 */
	protected function contentTable($header, $rows, $brightenFirst = true)
	{
		$code = '<table class="content-table">';

		/*
		 * Table head
		 */
		$code .= '<tr>';
		foreach ($header as $value) {
			$code .= '<th>';
			$code .= $value;
			$code .= '</th>';
		}
		$code .= '</tr>';

		/*
		 * Table body
		 */
		foreach ($rows as $row) {
			$code 	.= '<tr>';
			$isFirst = true;
			foreach ($row as $value) {
				if ($isFirst and $brightenFirst) {
					$code 	.= '<td style="color: silver">';
					$isFirst = false;
				} else {
					$code .= '<td>';
				}
				$code .= $value;
				$code .= '</td>';
			}
			$code .= '</tr>';
		}

		$code .= '</table>';

		return $code;
	}
}