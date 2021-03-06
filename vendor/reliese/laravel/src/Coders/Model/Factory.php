<?php

/**
 * Created by Cristian.
 * Date: 19/09/16 11:58 PM.
 */
namespace Reliese\Coders\Model;

use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Reliese\Database\Eloquent\BaseValidator;
use Reliese\Meta\Blueprint;
use Reliese\Meta\SchemaManager;
use Reliese\Support\Classify;

class Factory
{
    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    private $db;

    /**
     * @var \Reliese\Meta\SchemaManager
     */
    protected $schemas;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Reliese\Support\Classify
     */
    protected $class;

    /**
     * @var RulesGenerator
     */
    protected $rulesGenerator;

    /**
     * @var \Reliese\Coders\Model\Config
     */
    protected $config;

    /**
     * @var \Reliese\Coders\Model\ModelManager
     */
    protected $models;

    /**
     * @var \Reliese\Coders\Model\Mutator[]
     */
    protected $mutators = [];

    /**
     * ModelsFactory constructor.
     *
     * @param \Illuminate\Database\DatabaseManager $db
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param RulesGenerator $rulesGenerator
     * @param \Reliese\Support\Classify $writer
     * @param \Reliese\Coders\Model\Config $config
     */
    public function __construct(DatabaseManager $db, Filesystem $files, RulesGenerator $rulesGenerator, Classify $writer, Config $config)
    {
        $this->db = $db;
        $this->files = $files;
        $this->rulesGenerator = $rulesGenerator;
        $this->config = $config;
        $this->class = $writer;
    }

    /**
     * @return \Reliese\Coders\Model\Mutator
     */
    public function mutate()
    {
        return $this->mutators[] = new Mutator();
    }

    /**
     * @return \Reliese\Coders\Model\ModelManager
     */
    protected function models()
    {
        if (! isset($this->models)) {
            $this->models = new ModelManager($this);
        }

        return $this->models;
    }

    /**
     * Select connection to work with.
     *
     * @param string $connection
     *
     * @return $this
     */
    public function on($connection = null)
    {
        $this->schemas = new SchemaManager($this->db->connection($connection));

        return $this;
    }

    /**
     * @param string $schema
     */
    public function map($schema)
    {
        if (! isset($this->schemas)) {
            $this->on();
        }

        $mapper = $this->makeSchema($schema);

        foreach ($mapper->tables() as $blueprint) {
            if ($this->shouldNotExclude($blueprint)) {
                $this->create($mapper->schema(), $blueprint->table());
            }
        }
    }

    /**
     * @param \Reliese\Meta\Blueprint $blueprint
     *
     * @return bool
     */
    protected function shouldNotExclude(Blueprint $blueprint)
    {
        foreach ($this->config($blueprint, 'except', []) as $pattern) {
            if (Str::is($pattern, $blueprint->table())) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $schema
     * @param string $table
     */
    public function create($schema, $table)
    {
        $model = $this->makeModel($schema, $table);

        if ($this->withValidators($model)) {
            $this->generateValidator($model);
            $model->withValidator(
                $this->validator($model)
            );
        }

        $template = $this->prepareTemplate($model, 'model');

        $file = $this->fillTemplate($template, $model);

        $this->files->put($this->modelPath($model, $model->usesBaseFiles() ? ['Base'] : []), $file);

        if ($this->needsUserFile($model)) {
            $this->createUserFile($model);
        }
    }

    /**
     * @param string $schema
     * @param string $table
     *
     * @param bool $withRelations
     *
     * @return \Reliese\Coders\Model\Model
     */
    public function makeModel($schema, $table, $withRelations = true)
    {
        return $this->models()->make($schema, $table, $this->mutators, $withRelations);
    }

    /**
     * @param string $schema
     *
     * @return \Reliese\Meta\Schema
     */
    public function makeSchema($schema)
    {
        return $this->schemas->make($schema);
    }

    /**
     * @param \Reliese\Coders\Model\Model $model
     * @todo: Delegate workload to SchemaManager and ModelManager
     *
     * @return array
     */
    public function referencing(Model $model)
    {
        $references = [];

        // TODO: SchemaManager should do this
        foreach ($this->schemas as $schema) {
            $references = array_merge($references, $schema->referencing($model->getBlueprint()));
        }

        // TODO: ModelManager should do this
        foreach ($references as &$related) {
            $blueprint = $related['blueprint'];
            $related['model'] = $model->getBlueprint()->is($blueprint->schema(), $blueprint->table())
                                ? $model
                                : $this->makeModel($blueprint->schema(), $blueprint->table(), false);
        }

        return $references;
    }

    /**
     * @param \Reliese\Coders\Model\Model $model
     * @param string $name
     *
     * @return string
     */
    protected function prepareTemplate(Model $model, $name)
    {
        $defaultFile = $this->path([__DIR__, 'Templates', $name]);
        $file = $this->config($model->getBlueprint(), "*.template.$name", $defaultFile);

        return $this->files->get($file);
    }

    /**
     * @param string $template
     * @param \Reliese\Coders\Model\Model $model
     *
     * @return mixed
     */
    protected function fillTemplate($template, Model $model)
    {
        $template = str_replace('{{date}}', Carbon::now()->toRssString(), $template);
        $template = str_replace('{{namespace}}', $model->getBaseNamespace(), $template);
        $template = str_replace('{{parent}}', $model->getParentClass(), $template);
        $template = str_replace('{{properties}}', $this->properties($model), $template);
        $template = str_replace('{{class}}', $model->getClassName(), $template);
        $template = str_replace('{{body}}', $this->body($model), $template);

        return $template;
    }

    /**
     * @param \Reliese\Coders\Model\Model $model
     *
     * @return string
     */
    protected function properties(Model $model)
    {
        // Process property annotations
        $annotations = '';

        foreach ($model->getProperties() as $name => $hint) {
            $annotations .= $this->class->annotation('property', "$hint \$$name");
        }

        if ($model->hasRelations()) {
            // Add separation between model properties and model relations
            $annotations .= "\n * ";
        }

        foreach ($model->getRelations() as $name => $relation) {
            // TODO: Handle collisions, perhaps rename the relation.
            if ($model->hasProperty($name)) {
                continue;
            }
            $annotations .= $this->class->annotation('property', $relation->hint()." \$$name");
        }

        return $annotations;
    }

    /**
     * @param \Reliese\Coders\Model\Model $model
     *
     * @return string
     */
    protected function body(Model $model)
    {
        $body = '';

        foreach ($model->getTraits() as $trait) {
            $body .= $this->class->mixin($trait);
        }

        if ($model->hasValidator()) {
            $body .= $this->class->field('validator', $model->validator(), ['visibility' => 'protected static']);
        }

        if ($model->hasCustomCreatedAtField()) {
            $body .= $this->class->constant('CREATED_AT', $model->getCreatedAtField());
        }

        if ($model->hasCustomUpdatedAtField()) {
            $body .= $this->class->constant('UPDATED_AT', $model->getUpdatedAtField());
        }

        if ($model->hasCustomDeletedAtField()) {
            $body .= $this->class->constant('DELETED_AT', $model->getDeletedAtField());
        }

        foreach ($model->getCustomConstants() as $constant => $value) {
            $body .= $this->class->constant($constant, $value);
        }

        foreach ($model->getEnumsValues() as $constant => $value) {
            $body .= $this->class->constant($constant, $value);
        }

        $body = trim($body, "\n");
        // Separate constants from fields only if there are constants.
        if (! empty($body)) {
            $body .= "\n";
        }

        // Append connection name when required
        if ($model->shouldShowConnection()) {
            $body .= $this->class->field('connection', $model->getConnectionName());
        }

        // When table is not plural, append the table name
        if ($model->needsTableName()) {
            $body .= $this->class->field('table', $model->getTableForQuery());
        }

        if ($model->hasCustomPrimaryKey()) {
            $body .= $this->class->field('primaryKey', $model->getPrimaryKey());
        }

        if ($model->doesNotAutoincrement()) {
            $body .= $this->class->field('incrementing', false, ['visibility' => 'public']);
        }

        if ($model->hasCustomPerPage()) {
            $body .= $this->class->field('perPage', $model->getPerPage());
        }

        if (! $model->usesTimestamps()) {
            $body .= $this->class->field('timestamps', false, ['visibility' => 'public']);
        }

        if ($model->hasCustomDateFormat()) {
            $body .= $this->class->field('dateFormat', $model->getDateFormat());
        }

        if ($model->doesNotUseSnakeAttributes()) {
            $body .= $this->class->field('snakeAttributes', false, ['visibility' => 'public static']);
        }

        if ($model->hasCasts()) {
            $body .= $this->class->field('casts', $model->getCasts(), ['before' => "\n"]);
        }

        if ($model->hasDates()) {
            $body .= $this->class->field('dates', $model->getDates(), ['before' => "\n"]);
        }

        if ($model->hasHidden() && $model->doesNotUseBaseFiles()) {
            $body .= $this->class->field('hidden', $model->getHidden(), ['before' => "\n"]);
        }

        if ($model->hasFillable() && $model->doesNotUseBaseFiles()) {
            $body .= $this->class->field('fillable', $model->getFillable(), ['before' => "\n"]);
        }

        if ($model->hasHints() && $model->usesHints()) {
            $body .= $this->class->field('hints', $model->getHints(), ['before' => "\n"]);
        }

        foreach ($model->getMutations() as $mutation) {
            $body .= $this->class->method($mutation->name(), $mutation->body(), ['before' => "\n"]);
        }

        foreach ($model->getRelations() as $constraint) {
            $body .= $this->class->method($constraint->name(), $constraint->body(), ['before' => "\n"]);
        }

        // Make sure there not undesired line breaks
        $body = trim($body, "\n");

        return $body;
    }

    /**
     * @param \Reliese\Coders\Model\Model $model
     *
     * @param array $custom
     *
     * @return string
     */
    protected function modelPath(Model $model, $custom = [])
    {
        $modelsDirectory = $this->path(array_merge([$this->config($model->getBlueprint(), 'path')], $custom));

        $this->verifyDirectory($modelsDirectory);

        return $this->path([$modelsDirectory, $model->getClassName().'.php']);
    }

    /**
     * @param array $pieces
     *
     * @return string
     */
    protected function path($pieces)
    {
        return implode(DIRECTORY_SEPARATOR, (array) $pieces);
    }

    /**
     * @param \Reliese\Coders\Model\Model $model
     *
     * @return bool
     */
    public function needsUserFile(Model $model)
    {
        return ! $this->files->exists($this->modelPath($model)) && $model->usesBaseFiles();
    }

    /**
     * @param \Reliese\Coders\Model\Model $model
     */
    protected function createUserFile(Model $model)
    {
        $file = $this->modelPath($model);

        $template = $this->prepareTemplate($model, 'user_model');
        $template = str_replace('{{namespace}}', $model->getNamespace(), $template);
        $template = str_replace('{{class}}', $model->getClassName(), $template);
        $template = str_replace('{{parent}}', '\\'.$model->getBaseNamespace().'\\'.$model->getClassName(), $template);
        $template = str_replace('{{body}}', $this->userFileBody($model), $template);

        $this->files->put($file, $template);
    }

    /**
     * @param \Reliese\Coders\Model\Model $model
     *
     * @return string
     */
    protected function userFileBody(Model $model)
    {
        $body = '';

        if ($model->hasHidden()) {
            $body .= $this->class->field('hidden', $model->getHidden());
        }

        if ($model->hasFillable()) {
            $body .= $this->class->field('fillable', $model->getFillable(), ['before' => "\n"]);
        }

        // Make sure there is not an undesired line break at the end of the class body
        $body = ltrim(rtrim($body, "\n"), "\n");

        return $body;
    }

    /**
     * @param \Reliese\Meta\Blueprint|null $blueprint
     * @param string $key
     * @param mixed $default
     *
     * @return mixed|\Reliese\Coders\Model\Config
     */
    public function config(Blueprint $blueprint = null, $key = null, $default = null)
    {
        if (is_null($blueprint)) {
            return $this->config;
        }

        return $this->config->get($blueprint, $key, $default);
    }

    /**
     * @param Model $model
     * @return boolean
     */
    protected function withValidators(Model $model)
    {
        return $this->config(
            $model->getBlueprint(),
            'validator.withValidator',
            false
        );
    }

    /**
     * @param Model $model
     * @return string
     */
    protected function validator(Model $model)
    {
        $className = $this->getValidatorClassName($model);
        $namespace = $this->getValidatorNamespace($model);

        return "\\{$namespace}\\{$className}";
    }

    protected function generateValidator(Model $model)
    {
        $template = $this->prepareTemplate($model, 'validator');

        $file = $this->fillValidatorTemplate($template, $model);

        $this->files->put($this->validatorPath($model), $file);
    }

    /**
     * @param Model $model
     * @return string
     */
    protected function getValidatorNamespace(Model $model)
    {
        return $this->config(
            $model->getBlueprint(),
            'validator.namespace',
            ''
        );
    }

    /**
     * @param $directory
     */
    protected function verifyDirectory($directory)
    {
        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * @param string $template
     * @param \Reliese\Coders\Model\Model $model
     *
     * @return mixed
     */
    protected function fillValidatorTemplate($template, Model $model)
    {
        $template = str_replace('{{date}}', Carbon::now()->toRssString(), $template);
        $template = str_replace('{{namespace}}', $this->getValidatorNamespace($model), $template);
        $template = str_replace('{{parent}}', $this->getValidatorParentClass(), $template);
        $template = str_replace('{{class}}', $this->getValidatorClassName($model), $template);
        $template = str_replace('{{body}}', $this->validatorBody($model), $template);

        return $template;
    }

    /**
     * @param Model $model
     * @return string
     */
    protected function getValidatorClassName(Model $model)
    {
        return "{$model->getClassName()}Validator";
    }

    /**
     * @return string
     */
    protected function getValidatorParentClass()
    {
        return BaseValidator::class;
    }

    /**
     * @param Model $model
     * @return string
     */
    protected function validatorBody(Model $model)
    {
        $body = '';
        $this->rulesGenerator->generate($model,
	        $this->rulePatterns($model),
	        $this->filterPatterns($model),
	        $this->config($model->getBlueprint(), 'validator.lookUp')
        );

        $body .= $this->class->field('rules', $this->rulesGenerator->rules(), ['visibility' => 'protected static']);
        $body .= $this->class->field('serverRules', $this->rulesGenerator->serverRules(), ['visibility' => 'protected static']);
        $body .= $this->class->field('filters', $this->rulesGenerator->filters(), ['visibility' => 'protected static']);
        $body .= $this->class->field('messages', [], ['visibility' => 'protected static']);

        // Make sure there not undesired line breaks
        $body = trim($body, "\n");

        return $body;
    }

	/**
	 * @param Model $model
	 * @return array
	 */
	protected function filterPatterns(Model $model)
	{
		return $this->config(
			$model->getBlueprint(),
			'validator.filterPatterns',
			[]
		);
	}

	/**
	 * @param Model $model
	 * @return array
	 */
	protected function rulePatterns(Model $model)
	{
		return $this->config(
			$model->getBlueprint(),
			'validator.rulePatterns',
			[]
		);
	}



    /**
     * @param Model $model
     * @return array
     */
    protected function getValidatorPath(Model $model)
    {
        return $this->config(
            $model->getBlueprint(),
            'validator.path',
            ''
        );
    }

    /**
     * @param $model
     * @return string
     */
    protected function validatorPath($model)
    {
        $path = $this->getValidatorPath($model);
        $path = $this->path([$path]);
        $this->verifyDirectory($path);

        return $this->path([$path, $this->getValidatorClassName($model) . '.php']);
    }
}
