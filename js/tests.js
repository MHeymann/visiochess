/*
 * These are just some sample test cases to test how the QUnit library works.
 * These will be changed to test the actual functionality in the main.js file
 * To see the sample JUnit output run the tests.html file
*/


QUnit.module("Testing Comparisons");
QUnit.test( "Test 1: Testing numbers", function( assert ) {
  assert.ok( 1 == "1", "PASS!" );
});

QUnit.test("Test 2: Testing strings", function(assert){
    assert.ok("Hi There!!!" == "Hi There!!!", "PASS!");
});

QUnit.test("Test 3: This should fail", function(assert){
    assert.ok(4 == "four", "PASS!");
});


QUnit.module("Testing objects");
QUnit.test("Test 1", function(assert){
    var obj = {foo: "bar"};
    assert.deepEqual(obj, {foo: "bar"}, "PASS!")
});

QUnit.test("Test 2", function(assert){
    var obj = {foo: 1};
    assert.deepEqual(obj, {foo: 1}, "PASS!")
});

QUnit.test("Test 3: should fail", function(assert){
    var obj = {foo: "bar"};
    assert.deepEqual(obj, {foo: "foo"}, "PASS!")
});

