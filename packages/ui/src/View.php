<?php
namespace Core\UI;

use Core\UI\Components\Layout;
use Core\UI\Components\Table;
use Core\UI\Components\Form;
use Core\UI\Components\LoginPage;
use Core\UI\Components\SchemaForm;

class View
{
    public static function layout(string $title, string $content, array $tables = []): string
    {
        return Layout::render($title, $content, $tables);
    }

    public static function table(array $data, array $fields, string $table, string $sort = '', string $order = 'asc', string $basePath = ''): string
    {
        return Table::render($data, $fields, $table, $sort, $order, $basePath);
    }

    public static function form(array $fields, string $action, array $data = [], string $method = 'POST'): string
    {
        return Form::render($fields, $action, $data, $method);
    }

    public static function loginPage(string $csrfToken = ''): string
    {
        return LoginPage::render($csrfToken);
    }

    public static function schemaForm(array $meta = []): string
    {
        return SchemaForm::render($meta);
    }
}
