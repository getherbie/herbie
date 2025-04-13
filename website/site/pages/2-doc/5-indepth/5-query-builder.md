---
title: Query Builder
layout: doc
---

# Query Builder

Herbie provides a fluent query builder interacting with your content and data in PHP-land.

~~~php
$data = [
    ['title' => 'Foo', 'layout' => 'default'], 
    ['title' => 'Bar', 'layout' => 'blog'],
    ['title' => 'Baz', 'layout' => 'default'],      
];

var_dump(
  (new \herbie\QueryBuilder)
    ->from($data)
    ->where('layout=default')
    ->offset(1)
    ->limit(1)
    ->order('title')
    ->all()
);
~~~

In Twig the same query builder can be instantiated using the `query` function.

~~~twig
{{ '{%' }} set data = [
  {
    'title': 'Foo',
    'layout': 'default',
  },
  {
    'title': 'Bar',
    'layout': 'blog',
  },
  {
    'title': 'Baz',
    'layout': 'default',
  }
] {{ '%}' }} 

{{ '{{' }} dump(query(data).where("layout=default").offset(1).limit(1).order('title').all()) {{ '}}' }} 
~~~

Some list entites also have a built-in query method, so the query builder can be used as follows.

~~~twig
{{ '{{' }} dump(site.page_list.query().where("layout=default").all()) {{ '}}' }} 
~~~

NOTE: We are using Twigs `dump` function in these examples. You have to enable the function by setting `components.twigRenderer.debug` to `true` in your configuration file.

## Retrieving Data

A query builder allows you to query, filter, and narrow down the results you desire.
The page list query builder allows you to find all the entries in a collection, by a specific author, and so on.

### Getting multiple records

The query builder allows you to assemble a query, chain additional constraints onto it, and then invoke the `all` method to get the results:

~~~twig
{{ '{{' }} dump(query(site.page_list).where("layout=default").limit(5).all()) {{ '}}' }} 
~~~

This would return a list of the queried items.
In this particular example, you would have a list of five page objects.

### Getting a single record

If you only want to get a single record, you may use the `one` method. 
This method will return a single data object:

~~~twig
{{ '{{' }} dump(query(site.page_list).where("layout=default").one()) {{ '}}' }} 
~~~

## Where

The heart of the query builder are selectors, which are loosely based on CSS attribute selectors.

### Selectors

A selector is a simple text string that specifies fields and values, and that can be applied to a where condition.
It can be one of the following:

<table class="pure-table pure-table-horizontal">
<tr><td style='width:5%'>=</td><td>Equal to</td></tr>    
<tr><td>!=</td><td>Not equal to</td></tr>
<tr><td>&lt;</td><td>Less than</td></tr>    
<tr><td>&gt;</td><td>Greater than</td></tr>
<tr><td>&lt;=</td><td>Less than or equal to</td></tr>
<tr><td>&gt;=</td><td>Greater than or equal to</td></tr>
<tr><td>*=</td><td>Contains phrase/text</td></tr>
<tr><td>~=</td><td>Contains all words</td></tr>
<tr><td>^=</td><td>Starts with phrase/text</td></tr>
<tr><td>$=</td><td>Ends with phrase/text</td></tr>
<tr><td>?=</td><td>Match regular expression</td></tr>
<tr><td>&</td><td>Bitwise AND</td></tr>
</table>

With `where` clauses the result can be narrowed down as desired.
There are three different formats that can be used for this.

### String Format

The String format is best used to specify simple conditions. 
For example:

~~~twig
{{ '{{' }} dump(query(data).where("layout=default", "title*=news")) {{ '}}' }} 
~~~

You can chain where clauses, filtering records based on more than one condition with AND:

~~~twig
{{ '{{' }} dump(query(data).where("layout=default").where("title*=news").where("hidden=false")) {{ '}}' }} 
~~~

Values are type hinted according to the type of the field and one of the scalar types bool, float, int, or string.

~~~twig
{{ '{{' }} dump(query(data).where("hidden=false", "size=14.25", "age=24", "layout=default")) {{ '}}' }} 
~~~

#### Multiple Fields

If you want to match a value in one field or another, you may specify multiple fields separated by a pipe "|" symbol, i.e.

~~~twig
{{ '{{' }} dump(query(data).where("name|title|menu_title=product")) {{ '}}' }} 
~~~

Using the above syntax, the condition will match any data that have a name, title, or menu_title field of "product" or "Product".

#### Multiple Values

You may also specify an either/or value, by separating each of the values that may match with a pipe character "|".

~~~twig
{{ '{{' }} dump(query(data).where("layout=default|blog")) {{ '}}' }} 
~~~

#### Array Fields

If the queried field is an array, the operator is applied for each array items as OR conjunction.

~~~twig
{{ '{{' }} dump(query(data).where("tags=blog")) {{ '}}' }}
~~~

Using the above syntax, the query will match if one of the tags equals to "blog".

### Hash Format

The hash format is best used to specify multiple AND-concatenated sub-conditions each being a simple equality assertion.
It is written as an array whose keys are column names and values the corresponding values that the columns should be.
For example:

~~~twig
{{ '{{' }} dump(query(data).where({layout: "default", age: 24, size: 178.5, hidden: false})) {{ '}}' }} 
~~~

### Operator Format

The operator format is best used when you have more complex sub queries that are combined with an AND or OR where clause operator.
For example:

~~~twig
{{ '{{' }} dump(query(data).where(["OR", "layout=default", "title*=blog|news", "date>=2022-12-12"])) {{ '}}' }} 
~~~

With the syntax above, the individual conditions are OR conjuncted.

The AND/OR where clause operators can be nested:

~~~twig
{{ '{{' }} dump(query(data).where(["OR", ["AND", "layout=default", "title*=blog"], ["AND", "date>=2022-12-12", "cached=true"]])) {{ '}}' }} 
~~~

## Order

The results can be ordered using the `order` method.
The argument can be a field name preceded by a plus or minus sign, where plus means ascending and minus means descending.
If the plus or minus sign is omitted, the order is ascending by default.
Here are examples of its usage.

Ordered by title descending:

~~~twig
{{ '{{' }} dump(query(data).order("-title")) {{ '}}' }} 
~~~

Ordered by title ascending:

~~~twig
{{ '{{' }} dump(query(data).order("title")) {{ '}}' }} 
{{ '{{' }} dump(query(data).order("+title")) {{ '}}' }} 
~~~

## Limit

You may limit the results by using the `limit` method:

~~~twig
{{ '{{' }} dump(query(data).limit(10)) {{ '}}' }} 
~~~

## Offset

You may skip results by using the `offset` method:

~~~twig
{{ '{{' }} dump(query(data).offset(10).limit(10)) {{ '}}' }} 
~~~

## Count

The query builder also provides a count method for retrieving the number of records returned.

~~~twig
{{ '{{' }} dump(query(data).where("layout=default").count()) {{ '}}' }} 
~~~

## Paginate

If you want to get paginated results on a query, you may use the `paginate` method and specify the desired number of results per page.

~~~twig
{{ '{{' }} dump(query(data).where("layout=default").paginate(10)) {{ '}}' }}
~~~

This will return an instance of `herbie\Pagination` that you can use to assemble the pagination style of your choice.
