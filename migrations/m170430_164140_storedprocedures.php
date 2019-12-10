<?php

use yii\db\Migration;

class m170430_164140_storedprocedures extends Migration
{
    public function up()
    {
        $balanceAddSql = <<< SQL
DROP FUNCTION IF EXISTS getMultiplier;
CREATE FUNCTION getMultiplier()
RETURNS INT
BEGIN
   RETURN 100000;
END;
 
DROP FUNCTION IF EXISTS balanceAdd;
CREATE FUNCTION balanceAdd(
    userId INT,
    amount DECIMAL(20,5)
)
RETURNS INT
BEGIN
    DECLARE newBalance BIGINT DEFAULT 0;
    DECLARE numRows BOOLEAN DEFAULT 0;
    DECLARE multiplier INT DEFAULT 0;
    SET multiplier = (SELECT getMultiplier());

    SET newBalance = (SELECT ua.balance FROM UserAccount ua WHERE ua.uid=userId FOR UPDATE);
    SET numRows = FOUND_ROWS();


    IF numRows = 0 THEN
        #if there is no balance yet we should create.
	SET newBalance = amount * multiplier;
        INSERT INTO UserAccount(`uid`,`balance`) VALUES(userId,newBalance);
    ELSE 
        #if it exists just update
	SET newBalance = newBalance + (amount * multiplier);
        UPDATE UserAccount SET `balance`=newBalance WHERE `uid`=userId;
    END IF; 

    RETURN 1;
END;
$$
SQL;

        $balanceSubstractSql = <<< SQL
 DROP FUNCTION IF EXISTS balanceSubstract;
CREATE FUNCTION balanceSubstract(
    userId INT,
    amount DECIMAL(20,5)
)
RETURNS INT
BEGIN
    DECLARE newBalance BIGINT DEFAULT 0;
    DECLARE numRows BOOLEAN DEFAULT 0;
    DECLARE multiplier INT DEFAULT 0;

    SET multiplier = (SELECT getMultiplier());

    SET newBalance = (SELECT ua.balance FROM UserAccount ua WHERE ua.uid=userId FOR UPDATE);
    SET numRows = FOUND_ROWS();

    SET newBalance = newBalance - (amount * multiplier);
    IF (numRows = 0 OR newBalance < 0) THEN
        #if there is no balance, we cant let user go bellow 0
	RETURN 0;
    ELSE
        UPDATE UserAccount SET `balance`=newBalance WHERE `uid`=userId;
	RETURN 1;
    END IF;
END
SQL;

        $reserveSql = <<< SQL
DROP FUNCTION IF EXISTS reserve;
CREATE FUNCTION reserve(
    userId INT,
    amount DECIMAL(20,5)
)
RETURNS INT
BEGIN
    DECLARE newBalance BIGINT DEFAULT 0;
    DECLARE reserveSum BIGINT DEFAULT 0;
    DECLARE numRows BOOLEAN DEFAULT 0;
    DECLARE multiplier INT DEFAULT 0;

    SET multiplier = (SELECT getMultiplier());

    SET reserveSum = amount * multiplier;

    SET newBalance = (SELECT ua.balance FROM UserAccount ua WHERE ua.uid=userId FOR UPDATE);
    SET numRows = FOUND_ROWS();

    SET newBalance = newBalance - reserveSum;
    IF numRows = 0 OR newBalance <0 THEN
        #if there is no balance, we can let user go bellow 0
        RETURN 0;
    ELSE
        INSERT INTO UserReservations(`uid`,`amount`) VALUES (userId,reserveSum);
        UPDATE UserAccount SET `balance`=newBalance WHERE `uid`=userId;
	    RETURN 1;
    END IF;
 END;
SQL;

        $approveSql = <<< SQL
DROP FUNCTION IF EXISTS approve;
CREATE FUNCTION approve(
    transactionId INT
)
RETURNS INT
BEGIN
    DECLARE numRows BOOLEAN DEFAULT 0;
    DECLARE multiplier INT DEFAULT 0;

    SET multiplier = (SELECT getMultiplier());

    DELETE FROM UserReservations WHERE `id`=transactionId;
    SET numRows = ROW_COUNT();

    IF numRows = 1 THEN
	     RETURN 1;
    ELSE
	     RETURN 0;
    END IF;
END;
SQL;

        $declineSql = <<< SQL
DROP FUNCTION IF EXISTS decline;
CREATE FUNCTION decline(
    transactionId INT
)
RETURNS INT
BEGIN
    DECLARE numRows BOOLEAN DEFAULT 0;
    DECLARE amountOfReservation BIGINT DEFAULT 0;
    DECLARE userId INT DEFAULT 0;
    DECLARE multiplier INT DEFAULT 0;

    SELECT ur.amount, ur.uid INTO amountOfReservation,userId FROM UserReservations ur WHERE ur.id=transactionId FOR UPDATE;
    SET numRows = FOUND_ROWS();

    IF numRows = 1 THEN
        DELETE FROM UserReservations WHERE `id`=transactionId;
        UPDATE UserAccount SET balance = balance + (amountOfReservation) WHERE `uid` =userId;
        RETURN 1;
    ELSE
     	RETURN 0;
    END IF;
END;
SQL;

        $transferSql = <<< SQL
DROP FUNCTION IF EXISTS transfer;
CREATE FUNCTION transfer(
    sourceUserId INT,
    targetUserId INT,
    amount DECIMAL(20,5)
)
RETURNS INT
BEGIN
    DECLARE result INT DEFAULT 0;

    SET result = balanceSubstract(sourceUserId,amount);
    IF result = 0 THEN
       RETURN 0;
    ELSE
        SET result = balanceAdd(targetUserId,amount);
        IF result = 0 THEN
           RETURN 0;
        ELSE
           RETURN 1;
        END IF;
    END IF;

END;
SQL;


        $this->execute($balanceAddSql);
        $this->execute($balanceSubstractSql);
        $this->execute($reserveSql);
        $this->execute($declineSql);
        $this->execute($approveSql);
        $this->execute($transferSql);
    }

    public function down()
    {
        //due of UP code syntax, there is no need to revert
        return true;
    }

}
