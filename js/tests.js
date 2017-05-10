/* global QUnit */
/* global validYearRange */
QUnit.test('validYearRange()', function(assert) {
    assert.ok(validYearRange(2000, 1990) == false, "2000 to 1990 is not valid");
    assert.ok(validYearRange(2000, 2003) == true, "2000 to 2003 is valid");
    assert.ok(validYearRange("abc", 1990) == true, "'abc' to 1990 is valid ");
});

QUnit.test('validEcoRange()', function(assert) {
    assert.ok(validEcoRange(200, 199) == false, "200 to 199 is not valid");
    assert.ok(validEcoRange(100, 300) == true, "100 to 300 is valid");
    assert.ok(validEcoRange(1990, "") == true, "1990 to '' is valid");
});

QUnit.test('validBlackEloRange()', function(assert) {
    assert.ok(validBlackEloRange(2100, 2005) == false, "2100 to 2005 is not valid");
    assert.ok(validBlackEloRange(2100, 2105) == true, "2100 to 2105 is valid");
    assert.ok(validBlackEloRange("2000", "2005") == true, "'2000' to '2005' is valid");
});

QUnit.test('validWhiteEloRange()', function(assert) {
    assert.ok(validWhiteEloRange(3000, 2115) == false, "3000 to 2115 is not valid");
    assert.ok(validWhiteEloRange(2115, 2105) == false, "2115 to 2105 is not valid");
    assert.ok(validWhiteEloRange("blou is die wolke", "blou is die lug") == true, "'blou is die wolke' to 'blou is die lug' is valid");
});
