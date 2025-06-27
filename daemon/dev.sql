SET @place_code = (
SELECT
	irace.place.place_code
FROM
	irace.race
	LEFT JOIN irace.place ON irace.race.place_id = irace.place.id
WHERE
	irace.race.id = new.race_id);

SET @association_code = new.association_code;

IF @place_code = 'jeju' THEN
	SET @association_code = 'kjrace';

END IF;
#set @s := concat('set @finish_time_offset = ', '(select ifnull(ubo.bo_', @association_code, '_finish_time_offset,ifnull(pbo.bo_', @association_code, '_finish_time_offset,ifnull(cbo.bo_', @association_code, '_finish_time_offset,bbo.bo_', @association_code, '_finish_time_offset)))', ' FROM isports.users LEFT JOIN isports.betting_options AS ubo ON ubo.bo_user_id = isports.users.id LEFT JOIN	isports.betting_options AS cbo ON cbo.bo_user_class_id = isports.users.user_class_id LEFT JOIN	isports.betting_options AS bbo ON bbo.bo_branch_id = isports.users.branch_id WHERE isports.users.id = ', new.user_id, ')');
#CALL eval (@association_code);
set @finish_time_offset = 
	(SELECT
    case new.association_code   
      when 'krace' then
        ifnull(ubo.bo_krace_finish_time_offset,ifnull(cbo.bo_krace_finish_time_offset,bbo.bo_krace_finish_time_offset))
      when 'kjrace' then
        ifnull(ubo.bo_kjrace_finish_time_offset,ifnull(cbo.bo_kjrace_finish_time_offset,bbo.bo_kjrace_finish_time_offset))
      when 'jrace' then
        ifnull(ubo.bo_jrace_finish_time_offset,ifnull(cbo.bo_jrace_finish_time_offset,bbo.bo_jrace_finish_time_offset))
      when 'jra' then
        ifnull(ubo.bo_jra_finish_time_offset,ifnull(cbo.bo_jra_finish_time_offset,bbo.bo_jra_finish_time_offset))
      when 'jcycle' then 
        ifnull(ubo.bo_jcycle_finish_time_offset,ifnull(cbo.bo_jcycle_finish_time_offset,bbo.bo_jcycle_finish_time_offset))
      when 'jboat' then
        ifnull(ubo.bo_jboat_finish_time_offset,ifnull(cbo.bo_jboat_finish_time_offset,bbo.bo_jboat_finish_time_offset))
      when 'jbike' then
        ifnull(ubo.bo_jbike_finish_time_offset,ifnull(cbo.bo_jbike_finish_time_offset,bbo.bo_jbike_finish_time_offset))
      when 'kcycle' then
        ifnull(ubo.bo_kcycle_finish_time_offset,ifnull(cbo.bo_kcycle_finish_time_offset,bbo.bo_kcycle_finish_time_offset))
      when 'kboat' then
        ifnull(ubo.bo_kboat_finish_time_offset,ifnull(cbo.bo_kboat_finish_time_offset,bbo.bo_kboat_finish_time_offset))
      when 'osr' then
        ifnull(ubo.bo_osr_finish_time_offset,ifnull(cbo.bo_osr_finish_time_offset,bbo.bo_osr_finish_time_offset))
      when 'osh' then
        ifnull(ubo.bo_osh_finish_time_offset,ifnull(cbo.bo_osh_finish_time_offset,bbo.bo_osh_finish_time_offset))
      when 'osg' then
        ifnull(ubo.bo_osg_finish_time_offset,ifnull(cbo.bo_osg_finish_time_offset,bbo.bo_osg_finish_time_offset))  
      ELSE 0
    end
	FROM isports.users LEFT JOIN isports.betting_options AS ubo ON ubo.bo_user_id = isports.users.id 
	LEFT JOIN	isports.betting_options AS cbo ON cbo.bo_user_class_id = isports.users.user_class_id 
	LEFT JOIN	isports.betting_options AS bbo ON bbo.bo_branch_id = isports.users.branch_id 
	WHERE isports.users.id = new.user_id);

IF @finish_time_offset IS NULL THEN
	SET @finish_time_offset = 0;
END IF;

IF new.stat = 'C' AND DATE_ADD((
	SELECT
		start_time FROM irace.race
	WHERE
		id = old.race_id), INTERVAL - @finish_time_offset second) < now() THEN
	SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '취소 가능 시간 초과.';

ELSE
	SET @old_money_real = (
	SELECT
		money_real
	FROM
		users
	WHERE
		users.id = new.user_id);

	SET @old_money_service_race = (
	SELECT
		money_service_race
	FROM
		users
	WHERE
		users.id = new.user_id);
	
	IF (new.stat = 'C' OR new.stat = 'R') AND old.stat != 'R' AND old.stat != 'C' AND new.money_type = 'R' THEN
		UPDATE
			users
		SET
			users.money_service_race = users.money_service_race - new.service_money_race
		WHERE
			users.id = new.user_id;
		
		INSERT INTO document (branch_id, user_id, money_type, system, division, cause, amount, subject, base_date, base_time, balance, balance_money_real, balance_money_point, balance_money_service, balance_money_service_race)
				select branch_id, id, 'money_service_race', 'race', 'race', if(new.stat = 'C', 'cancel', 'refund'), new.service_money_race*-1, 'race cancel(refund) - service back', date(now()), now(), money_service_race, money_real, money_point, money_service, money_service_race from users where id = NEW.user_id; 
	
		UPDATE
			users
		SET
			users.money_real = users.money_real + new.bet_money
		WHERE
			users.id = new.user_id;
		
		INSERT INTO document (branch_id, user_id, money_type, system, division, cause, amount, subject, base_date, base_time, balance, balance_money_real, balance_money_point, balance_money_service, balance_money_service_race)
				select branch_id, id, 'money_real', 'race', 'race', if(new.stat = 'C', 'cancel', 'refund'), new.bet_money, 'race cancel(refund) - money back', date(now()), now(), money_real, money_real, money_point, money_service, money_service_race from users where id = NEW.user_id; 
	
		IF old.stat = 'W' THEN
			UPDATE
				users
			SET
				users.money_real = users.money_real - new.result_money
			WHERE
				users.id = new.user_id;
			
			INSERT INTO document (branch_id, user_id, money_type, system, division, cause, amount, subject, base_date, base_time, balance, balance_money_real, balance_money_point, balance_money_service, balance_money_service_race)
				select branch_id, id, 'money_real', 'race', 'race', if(new.stat = 'C', 'cancel', 'refund'), new.result_money*-1, 'race cancel(refund) - paid money back', date(now()), now(), money_real, money_real, money_point, money_service, money_service_race from users where id = NEW.user_id; 
		
		END IF;
	
		INSERT INTO `logs` (`old_money_real`, `new_money_real`, `old_money_service_race`, `new_money_service_race`, `memo`, `user_id`, `branch_id`)
		SELECT
			@old_money_real,
			money_real,
			@old_money_service_race,
			money_service_race,
			concat((
				SELECT
					concat(p.name, ' ', r.race_no, '경주 ', new.type, ' ', new.place_1, '-', new.place_2, '-', new.place_3)
					FROM irace.race AS r
				LEFT OUTER JOIN irace.place AS p ON p.id = r.place_id
		WHERE
			r.id = new.race_id), ' ', new.bet_money, ' 원 ', if(new.stat = 'C', '취소로 인한 변동', '환불로 인한 변동')), new.user_id, new.branch_id
		FROM
			users
		WHERE
			users.id = new.user_id;
	
	elseif (new.stat = 'C'
		OR new.stat = 'R')
	AND old.stat != 'R'
	AND old.stat != 'C'
	AND new.money_type = 'S' THEN
		UPDATE
			users
		SET
			users.money_service_race = users.money_service_race + new.bet_money
		WHERE
			users.id = new.user_id;
		
		INSERT INTO document (branch_id, user_id, money_type, system, division, cause, amount, subject, base_date, base_time, balance, balance_money_real, balance_money_point, balance_money_service, balance_money_service_race)
				select branch_id, id, 'money_service_race', 'race', 'race', if(new.stat = 'C', 'cancel', 'refund'), new.bet_money, 'race cancel(refund) - service money back', date(now()), now(), money_service_race, money_real, money_point, money_service, money_service_race from users where id = NEW.user_id; 
	
	
		IF old.stat = 'W' THEN
			UPDATE
				users
			SET
				users.money_real = users.money_real - new.result_money
			WHERE
				users.id = new.user_id;
			
			INSERT INTO document (branch_id, user_id, money_type, system, division, cause, amount, subject, base_date, base_time, balance, balance_money_real, balance_money_point, balance_money_service, balance_money_service_race)
				select branch_id, id, 'money_real', 'race', 'race', if(new.stat = 'C', 'cancel', 'refund'), new.result_money*-1, 'race cancel(refund) - paid money back', date(now()), now(), money_real, money_real, money_point, money_service, money_service_race from users where id = NEW.user_id; 
		
		END IF;
	
		INSERT INTO `logs` (`old_money_real`, `new_money_real`, `old_money_service_race`, `new_money_service_race`, `memo`, `user_id`, `branch_id`)
		SELECT
			@old_money_real,
			money_real,
			@old_money_service_race,
			money_service_race,
			concat((
				SELECT
					concat(p.name, ' ', r.race_no, '경주 ', new.type, ' ', new.place_1, '-', new.place_2, '-', new.place_3)
					FROM irace.race AS r
				LEFT OUTER JOIN irace.place AS p ON p.id = r.place_id
		WHERE
			r.id = new.race_id), ' ', new.bet_money, ' 원 ', if(new.stat = 'C', '취소로 인한 변동', '환불로 인한 변동')), new.user_id, new.branch_id
		FROM
			users
		WHERE
			users.id = new.user_id;
	
	END IF;

END IF;