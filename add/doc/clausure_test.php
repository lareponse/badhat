<?php

require 'add/bad/dad/clausure.php';
require 'add/test.php';


return function () {

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


    $where = clause(CLAUSE_WHERE, '=');
    test('where: single condition', function () use ($where) {
        list($sql, $bind) = $where("id = 1 AND username = 'test'");
        assert($sql === 'WHERE id = 1 AND username = \'test\'', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });

    test('where: multiple conditions', function () use ($where) {
        list($sql, $bind) = $where("id = 1", "username = 'test'");
        assert($sql === 'WHERE id = 1 AND username = \'test\'', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });

    test('where: associative array', function () use ($where) {
        list($sql, $bind) = $where(['id' => 1, 'username' => 'test']);
        assert($sql === 'WHERE `id` = :id AND `username` = :username', 'wrong SQL');
        assert($bind === ['id' => 1, 'username' => 'test'], 'bindings should be empty');
    });

    $where = clause(CLAUSE_WHERE | OP_OR, '=');
    test('where: OR single condition', function () use ($where) {
        list($sql, $bind) = $where("id = 1 OR username = 'test'");
        assert($sql === 'WHERE id = 1 OR username = \'test\'', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });

    test('where: OR multiple conditions', function () use ($where) {
        list($sql, $bind) = $where("id = 1", "username = 'test'");
        assert($sql === 'WHERE id = 1 OR username = \'test\'', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });

    test('where: OR associative array', function () use ($where) {
        list($sql, $bind) = $where(['id' => 1, 'username' => 'test']);
        assert($sql === 'WHERE `id` = :id OR `username` = :username', 'wrong SQL');
        assert($bind === ['id' => 1, 'username' => 'test'], 'bindings should be empty');
    });


    $and = clause(OP_AND, '=');
    test('and: single condition', function () use ($and) {
        list($sql, $bind) = $and("id = 1 AND username = 'test'");
        assert($sql === '(id = 1 AND username = \'test\')', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });
    test('and: multiple conditions', function () use ($and) {
        list($sql, $bind) = $and("id = 1", "username = 'test'");
        assert($sql === '(id = 1 AND username = \'test\')', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });
    test('and: associative array', function () use ($and) {
        list($sql, $bind) = $and(['id' => 1, 'username' => 'test']);
        assert($sql === '(`id` = :id AND `username` = :username)', 'wrong SQL');
        assert($bind === ['id' => 1, 'username' => 'test'], 'bindings should be empty');
    });

    $or = clause(OP_OR, '=');
    test('or: single condition', function () use ($or) {
        list($sql, $bind) = $or("id = 1 OR username = 'test'");
        assert($sql === '(id = 1 OR username = \'test\')', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });
    test('or: multiple conditions', function () use ($or) {
        list($sql, $bind) = $or("id = 1", "username = 'test'");
        assert($sql === '(id = 1 OR username = \'test\')', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });
    test('or: associative array', function () use ($or) {
        list($sql, $bind) = $or(['id' => 1, 'username' => 'test']);
        assert($sql === '(`id` = :id OR `username` = :username)', 'wrong SQL');
        assert($bind === ['id' => 1, 'username' => 'test'], 'bindings should be empty');
    });


    test('and with nested or', function () use ($and, $or) {
        list($sql, $bind) = $and(
            "id = 1",
            $or("username = 'test'", "email = 'test@test.com'"),
            "status = 'active'"
        );
        assert($sql === '(id = 1 AND (username = \'test\' OR email = \'test@test.com\') AND status = \'active\')', 'wrong SQL');
        assert($bind === [], 'bindings should be empty');
    });

    test('and with nested or', function () use ($and, $or) {
        list($sql, $bind) = $and(
            ['id' => 1],
            $or(['username' => 'test', "email" => 'test@test.com']),
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
    // $query = clause(CLAUSE_QUERY);
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
