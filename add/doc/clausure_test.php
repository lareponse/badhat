<?php

require 'add/bad/clausure.php';
require 'add/bad/test.php';


return function () {
    // clause(CLAUSE_SELECT)
    test('select: single comma-string', function () {
        list($sql, $bind) = clause(CLAUSE_SELECT)('id,COUNT(*),username,email');
        assert($sql === 'SELECT id,COUNT(*),username,email', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });

    test('select: multiple args', function () {
        list($sql, $bind) = clause(CLAUSE_SELECT)('id', 'COUNT(*)', 'username', 'email');
        assert($sql === 'SELECT id, COUNT(*), username, email', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });

    test('select: array with named key', function () {
        list($sql, $bind) = clause(CLAUSE_SELECT)(['id', 'COUNT(*)', 'name', 'username' => 'email']);
        assert($sql === 'SELECT id, COUNT(*), name, email AS `username`', 'named key mapping failed');
        assert($bind === [], 'bindings should be empty');
    });

    // clause(CLAUSE_WHERE, '=')
    test('where: single condition', function () {
        list($sql, $bind) = clause(CLAUSE_WHERE, '=')("id = 1 AND username = 'test'");
        assert($sql === 'WHERE id = 1 AND username = \'test\'', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });
    test('where: multiple conditions', function () {
        list($sql, $bind) = clause(CLAUSE_WHERE, '=')("id = 1", "username = 'test'");
        assert($sql === 'WHERE id = 1 AND username = \'test\'', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });
    test('where: associative array', function () {
        list($sql, $bind) = clause(CLAUSE_WHERE, '=')(['id' => 1, 'username' => 'test']);
        assert($sql === 'WHERE `id` = :id AND `username` = :username', 'wrong SQL');
        assert($bind === ['id' => 1, 'username' => 'test'], 'bindings should be empty');
    });

    // clause(CLAUSE_WHERE | OP_OR, '=')
    test('where: OR single condition', function () {
        list($sql, $bind) = clause(CLAUSE_WHERE | OP_OR, '=')("id = 1 OR username = 'test'");
        assert($sql === 'WHERE id = 1 OR username = \'test\'', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });
    test('where: OR multiple conditions', function () {
        list($sql, $bind) = clause(CLAUSE_WHERE | OP_OR, '=')("id = 1", "username = 'test'");
        assert($sql === 'WHERE id = 1 OR username = \'test\'', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });
    test('where: OR associative array', function () {
        list($sql, $bind) = clause(CLAUSE_WHERE | OP_OR, '=')(['id' => 1, 'username' => 'test']);
        assert($sql === 'WHERE `id` = :id OR `username` = :username', 'wrong SQL');
        assert($bind === ['id' => 1, 'username' => 'test'], 'bindings should be empty');
    });


    // clause(OP_AND, '=');
    test('and: single condition', function () {
        list($sql, $bind) = clause(OP_AND, '=')("id = 1 AND username = 'test'");
        assert($sql === '(id = 1 AND username = \'test\')', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });
    test('and: multiple conditions', function () {
        list($sql, $bind) = clause(OP_AND, '=')("id = 1", "username = 'test'");
        vd($sql);

        assert($sql === '(id = 1 AND username = \'test\')', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });
    test('and: associative array', function () {
        list($sql, $bind) = clause(OP_AND, '=')(['id' => 1, 'username' => 'test']);
        assert($sql === '(`id` = :id AND `username` = :username)', 'wrong SQL');
        assert($bind === ['id' => 1, 'username' => 'test'], 'bindings should be empty');
    });

    // clause(OP_OR, '=');
    test('or: single condition', function () {
        list($sql, $bind) = clause(OP_OR, '=')("id = 1 OR username = 'test'");
        assert($sql === '(id = 1 OR username = \'test\')', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });
    test('or: multiple conditions', function () {
        list($sql, $bind) = clause(OP_OR, '=')("id = 1", "username = 'test'");
        assert($sql === '(id = 1 OR username = \'test\')', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });
    test('or: associative array', function () {
        list($sql, $bind) = clause(OP_OR, '=')(['id' => 1, 'username' => 'test']);
        assert($sql === '(`id` = :id OR `username` = :username)', 'wrong SQL');
        assert($bind === ['id' => 1, 'username' => 'test'], 'bindings should be empty');
    });

    // clause(OP_AND, '=')
    // clause(OP_OR, '=')
    test('and with nested or', function () {
        list($sql, $bind) = clause(OP_AND, '=')(
            "id = 1",
            clause(OP_OR, '=')("username = 'test'", "email = 'test@test.com'"),
            "status = 'active'"
        );
        assert($sql === '(id = 1 AND (username = \'test\' OR email = \'test@test.com\') AND status = \'active\')', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });

    test('and with nested or assoc', function () {
        list($sql, $bind) = clause(OP_AND, '=')(
            ['id' => 1],
            clause(OP_OR, '=')(['username' => 'test', "email" => 'test@test.com']),
            ['status' => 'active']
        );
        vd($sql);
        assert($sql === '(id = 1 AND (`username` = \'test\' OR `email` = \'test@test.com\') AND status = \'active\')', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });


    tests();

    // $value = clause(VALUES_LIST, 'ph');
    // vd($value("id, username, email"));
    // vd($value("id", "username", "email"));
    // vd($value(['id' => 1, 'username' => 'test', 'email']));
    // $select = clause(CLAUSE_SELECT, ',');

    // $where = clause(CLAUSE_WHERE, 'AND');
    // $andgt = clause(OP_AND, '>=');
    // $or = clause(OP_OR);
    // $in = clause(PH_LIST|OP_IN);
    // vd($in([3, 4]));
    // $order = clause(CLAUSE_ORDER_BY);

    // // vd(clause(OP_IN)("tag_id", [3, 4]));
    // [$q, $b] = statement(
    //     $select('id', 'COUNT(*)', 'username', 'email'),
    //     'FROM client',
    //     $where(
    //         "enabled_at > '1995-01-01'",
    //         $andgt(["enabled_at" => '2005-01-01']),
    //         $or("status = 'active'", "category = 'archive'"),
    //         $or($in("tag_id", [3, 4]), "tag_id IS NULL")
    //     ),
    //     $order('created_at', ['updated_at' => 'DESC'])
    // );
    // vd(1, $q, $b);

    // SELECT * 
    // FROM client 
    // WHERE enabled_at > 1995-01-01 
    // AND (status = 'active' OR category = 'archive') 
    // AND (tag_id IN(3, 4) OR tag_id IS NULL)
};
