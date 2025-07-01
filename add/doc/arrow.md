<?php
$article = row(db(), 'article');

// most calls returns the row state array
// except when using ROW_GET, then what you get is an array of what you asked for, 

// first
// load the article in ROW_LOAD with unique slug 'how-to-php', return internal state
$article(ROW_LOAD, ['slug' => 'how-to-php']); // the [] is nulled

// then
// get the assoc to fill the view form
$form_data = $article(ROW_GET | ROW_LOAD);

// after form submission
// load and alter the article
$article(ROW_LOAD, ['slug' => 'how-to-php']); // the [] is nulled
$article(ROW_SET, [
    'published_at' => date('Y-m-d H:i:s'),
    'title' => 'How to PHP',
    'article_tags' => ['php', 'tutorial']
]);
// finally
// save the changes to the database
$article(ROW_SAVE);
// resulting sql: UPDATE `article` SET `published_at` = '2023-10-01 12:00:00' WHERE `slug` = 'how-to-php';
// something seems missing? yes, it is coming in 10 lines **PROMISE**


// or, faster
$article = row(db(), 'article');
$form_data = $article(ROW_GET | ROW_LOAD, ['slug' => 'how-to-php']);

// then
$article(ROW_LOAD, ['slug' => 'how-to-php']); // the [] is nulled
$article(ROW_SET | ROW_SAVE, $post_data);


// now if your chair is on fire :
$default_form_data = row(db(), 'article')(ROW_GET | ROW_LOAD, ['slug' => 'this-has-default-form-values-for-inserts']);
// then
row(db(), 'article')(ROW_SET | ROW_EDIT | ROW_SAVE, $post_data);

// if your $post_data match the row schema, the record has been inserted (no ROW_LOAD)
// you can now huppel for your life

// **PROMISE**
// resulting sql: UPDATE `article` SET `published_at` = '2023-10-01 12:00:00' WHERE `slug` = 'how-to-php';
// ? title did not change from load, so no update for it
// ? article_tags is not part of the loaded schema, so it is not part of query

// !? are article_tags lost 
$auxiliary_data_assoc = $article(ROW_GET | ROW_MORE);

// ? can i see the validated changes
$fresh_data_assoc = $article(ROW_GET | ROW_EDIT);

// ? how does it know what to do with the article_tags
$field_list = $article(ROW_GET | ROW_SCHEMA);

// ? where does schema come from
// 10 more lines **PROMISE**

// ? can i bypass the schema control :
$article(ROW_SET | ROW_EDIT, ['published_at' => date('Y-m-d H:i:s')]);
$article(ROW_SET | ROW_MORE, ['subscription_consent' => date('Y-m-d H:i:s')]);
// ROW_MORE content are NOT saved in the database

//------ GETTING STUFF OUT --------------------
// to get the final merged state (everything loaded, with alterations applied), call
$data_assoc = $article(ROW_GET);
// this would be the same as $article(ROW_GET | ROW_LOAD | ROW_EDIT);

// to get the merged state of any array, call any combination of ROW_LOAD, ROW_EDIT, ROW_MORE
// whatever the combination order is, the order of precedence will be:
// 1. ROW_LOAD - the data loaded from the database
// 2. ROW_EDIT - the valid alterations from the request
// 3. ROW_MORE - the auxiliary data from the request
$data_assoc = $article(ROW_GET | ROW_LOAD | ROW_EDIT | ROW_MORE);

// only alterations, licit or not
$data_assoc = $article(ROW_GET | ROW_EDIT | ROW_MORE);

// **PROMISE**
//------ THE SCHEMA --------------------
// arrow does not introspect schema, it uses loaded content or single query SELECT * FROM .. LIMIT 1 when load is missing
// arrow assumes that updating a row starts by loading the row, giving all the information needed to create the persistence query

// when calling ROW_LOAD with a boat (an associative array with the primary key or unique key)
// it will set the schema from the keys of the loaded row

// if you want to set the schema, call
$article(ROW_SCHEMA | ROW_SET, ['slug', 'title', 'content', 'published_at']);

// if you want to set the schema using the select_schema function
$article(ROW_SCHEMA | ROW_SET);


//------ ERRORS --------------------
// if anything goes wrong during the save, the row will be in error state

// what went wrong?
$errors = $article(ROW_GET | ROW_ERROR);
// return null if no error