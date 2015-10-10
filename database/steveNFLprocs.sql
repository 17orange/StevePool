##### StevePoolUser functions
drop procedure if exists Login;
drop procedure if exists Logout;
drop procedure if exists SavePicks;
drop procedure if exists SaveConsolationPicks;
drop procedure if exists SaveWildCardPicks;
drop procedure if exists SaveDivisionalPicks;
drop procedure if exists SaveConferencePicks;
drop procedure if exists SaveSuperBowlPicks;
drop procedure if exists EditAccount;

##### StevePoolAdmin functions
drop procedure if exists AddUser;
drop procedure if exists EditUser;
drop procedure if exists AddUserToSeason;
drop procedure if exists RemoveUserFromSeason;
drop procedure if exists AddConference;
drop procedure if exists AddDivision;
drop procedure if exists AssignToDivision;
drop procedure if exists ChangeLockTime;
drop procedure if exists AdminSetPicks;

delimiter //






##### StevePoolUser functions
create procedure Login( in _uid    int unsigned , 
                        in _pword  char(32)     ,
                        in _IP     varchar(100) ,
                        in _bInfo  varchar(255) )
begin
  declare _userID int unsigned default 0;

  # grab the user in question
  select userID into _userID from User where password=_pword and userID=_uid;

  # make sure hes real
  if _userID > 0 then
    # clear his old sessions
    delete from Session where userID=_userID;

    # punch in a new one
    insert into Session (userID, IP) values (_userID, _IP);

    # add in the event
    insert into Event (userID, type, atTime, browserInfo) values (_userID, 'login', now(), _bInfo);
  end if;
end;  //

create procedure Logout( in _sessID  int unsigned , 
                         in _IP      varchar(100) )
begin
  # wipe the session in question
  delete from Session where sessionID=_sessID and IP=_IP;
end;  //

create procedure SavePicks( in _sessID   int unsigned , 
                            in _IP       varchar(100) ,
                            in _week     tinyint      ,
                            in _tieBreak smallint     ,
                            in _pick16   char(3)      ,
                            in _pick15   char(3)      ,
                            in _pick14   char(3)      ,
                            in _pick13   char(3)      ,
                            in _pick12   char(3)      ,
                            in _pick11   char(3)      ,
                            in _pick10   char(3)      ,
                            in _pick9    char(3)      ,
                            in _pick8    char(3)      ,
                            in _pick7    char(3)      ,
                            in _pick6    char(3)      ,
                            in _pick5    char(3)      ,
                            in _pick4    char(3)      ,
                            in _pick3    char(3)      ,
                            in _pick2    char(3)      ,
                            in _pick1    char(3)      )
begin
  # make sure theyve sent in the correct number of valid picks
  declare _numPicks tinyint unsigned default 0;
  declare _numGames tinyint unsigned default 0;
  declare _numGamesNeeded tinyint unsigned default 0;
  declare _numGamesMissed tinyint unsigned default 0;
  declare _numAlreadyLocked tinyint unsigned default 0;
  declare _minimumPoints tinyint unsigned default 0;
  declare _userID int unsigned default 0;
  select 16 - count(*) into _minimumPoints from Game where weekNumber=_week and season=
         (select value from Constants where name='fetchSeason');
  select if(_pick16!='',1,0) + if(_pick15!='',1,0) + if(_pick14!='',1,0) + if(_pick13!='',1,0) + if(_pick12!='',1,0) + 
         if(_pick11!='',1,0) + if(_pick10!='',1,0) + if(_pick9!='',1,0) + if(_pick8!='',1,0) + if(_pick7!='',1,0) + 
         if(_minimumPoints<6 and _pick6!='',1,0) + if(_minimumPoints<5 and _pick5!='',1,0) + 
         if(_minimumPoints<4 and _pick4!='',1,0) + if(_minimumPoints<3 and _pick3!='',1,0) + 
         if(_minimumPoints<2 and _pick2!='',1,0) + if(_minimumPoints<1 and _pick1!='',1,0) into _numPicks;
  select count(*) into _numGames from Game where weekNumber=_week and lockTime>now() and season=
         (select value from Constants where name='fetchSeason') and (homeTeam in 
         (_pick16, _pick15, _pick14, _pick13, _pick12, _pick11, _pick10, _pick9,
          _pick8, _pick7, _pick6, _pick5, _pick4, _pick3, _pick2, _pick1) or awayTeam in 
         (_pick16, _pick15, _pick14, _pick13, _pick12, _pick11, _pick10, _pick9,
          _pick8, _pick7, _pick6, _pick5, _pick4, _pick3, _pick2, _pick1));
  select count(*) into _numGamesMissed from Game where weekNumber=_week and lockTime>now() and season=
         (select value from Constants where name='fetchSeason') and (homeTeam not in 
         (_pick16, _pick15, _pick14, _pick13, _pick12, _pick11, _pick10, _pick9,
          _pick8, _pick7, _pick6, _pick5, _pick4, _pick3, _pick2, _pick1) and awayTeam not in 
         (_pick16, _pick15, _pick14, _pick13, _pick12, _pick11, _pick10, _pick9,
          _pick8, _pick7, _pick6, _pick5, _pick4, _pick3, _pick2, _pick1));
  select count(*) into _numGamesNeeded from Game where weekNumber=_week and lockTime>now() and season=
         (select value from Constants where name='fetchSeason');
  select count(*) into _numAlreadyLocked from Pick join Game using (gameID) join Session using (userID) 
         where sessionID=_sessID and points in (if(_pick16!='',16,0), 
         if(_pick15!='',15,0), if(_pick14!='',14,0), if(_pick13!='',13,0), if(_pick12!='',12,0), if(_pick11!='',11,0), 
         if(_pick10!='',10,0), if(_pick9!='',9,0), if(_pick8!='',8,0), if(_pick7!='',7,0), if(_pick6!='',6,0), 
         if(_pick5!='',5,0), if(_pick4!='',4,0), if(_pick3!='',3,0), if(_pick2!='',2,0), if(_pick1!='',1,0)) and 
         weekNumber=_week and lockTime<now() and season=(select value from Constants where name='fetchSeason');

  # make sure everything is set up correct-like
  if _numGames = _numGamesNeeded and _numPicks = _numGames and _numGamesMissed = 0 and _numAlreadyLocked = 0 then
    # save the winners they picked
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick16, points=16 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick16 or awayTeam=_pick16);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick15, points=15 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick15 or awayTeam=_pick15);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick14, points=14 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick14 or awayTeam=_pick14);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick13, points=13 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick13 or awayTeam=_pick13);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick12, points=12 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick12 or awayTeam=_pick12);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick11, points=11 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick11 or awayTeam=_pick11);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick10, points=10 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick10 or awayTeam=_pick10);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick9, points=9 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick9 or awayTeam=_pick9);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick8, points=8 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick8 or awayTeam=_pick8);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick7, points=7 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick7 or awayTeam=_pick7);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick6, points=6 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick6 or awayTeam=_pick6);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick5, points=5 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick5 or awayTeam=_pick5);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick4, points=4 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick4 or awayTeam=_pick4);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick3, points=3 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick3 or awayTeam=_pick3);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick2, points=2 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick2 or awayTeam=_pick2);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick1, points=1 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick1 or awayTeam=_pick1);
    update WeekResult join Session using (userID) set tieBreaker=_tieBreak where sessionID=_sessID and IP=_IP and 
      season = (select value from Constants where name='fetchSeason') and weekNumber=_week;

    # add an event for it
    select userID into _userID from Session where sessionID=_sessID;
    insert into Event (userID, gameID, type, atTime) values (_userID, _week, 'madePicks', now());
  end if;
end;  //

create procedure SaveConsolationPicks( in _sessID    int unsigned , 
                                       in _IP        varchar(100) ,
                                       in _wc1AFC    char(3)      ,
                                       in _wc2AFC    char(3)      ,
                                       in _wc1NFC    char(3)      ,
                                       in _wc2NFC    char(3)      ,
                                       in _div1AFC   char(3)      ,
                                       in _div2AFC   char(3)      ,
                                       in _div1NFC   char(3)      ,
                                       in _div2NFC   char(3)      ,
                                       in _confAFC   char(3)      ,
                                       in _confNFC   char(3)      ,
                                       in _superBowl char(3)      ,
                                       in _tieBreak  smallint     )
begin
  declare _numPicks tinyint unsigned default 0;
  declare _numStarted tinyint unsigned default 0;
  declare _userID int unsigned default 0;
  declare _season smallint;

  # make sure theyve sent in the correct number of valid picks
  select if(_wc1AFC!='',1,0) + if(_wc2AFC!='',1,0) + if(_wc1NFC!='',1,0) + if(_wc2NFC!='',1,0) + 
         if(_div1AFC!='',1,0) + if(_div2AFC!='',1,0) + if(_div1NFC!='',1,0) + if(_div2NFC!='',1,0) + 
         if(_confAFC!='',1,0) + if(_confNFC!='',1,0) + if(_superBowl!='',1,0) into _numPicks;

  # make sure no games have locked
  # fill in his results and give him all the null picks
  select cast(value as signed) into _season from Constants where name='fetchSeason';
  select count(*) into _numStarted from Game where weekNumber > 17 and season=_season and lockTime < now();

  # make sure everything is set up correct-like
  if _numPicks = 11 and _numStarted = 0 then
    # save the winners they picked
    update ConsolationResult join Session using (userID) set wc1AFC=_wc1AFC, wc2AFC=_wc2AFC, wc1NFC=_wc1NFC, wc2NFC=_wc2NFC,
      div1AFC=_div1AFC, div2AFC=_div2AFC, div1NFC=_div1NFC, div2NFC=_div2NFC, confAFC=_confAFC, confNFC=_confNFC,
      superBowl=_superBowl, tieBreaker=_tieBreak where sessionID=_sessID and IP=_IP and season=_season;

    # add an event for it
    select userID into _userID from Session where sessionID=_sessID;
    insert into Event (userID, gameID, type, atTime) values (_userID, _week, 'madePicks', now());
  end if;
end;  //

create procedure SaveWildCardPicks( in _sessID    int unsigned , 
                                    in _IP        varchar(100) ,
                                    in _tieBreak1 smallint     ,
                                    in _tieBreak2 smallint     ,
                                    in _tieBreak3 smallint     ,
                                    in _tieBreak4 smallint     ,
                                    in _pick4    char(3)      ,
                                    in _pick3    char(3)      ,
                                    in _pick2    char(3)      ,
                                    in _pick1    char(3)      )
begin
  # make sure theyve sent in the correct number of valid picks
  declare _week tinyint unsigned default 18;
  declare _numPicks tinyint unsigned default 0;
  declare _numGames tinyint unsigned default 0;
  declare _numGamesNeeded tinyint unsigned default 0;
  declare _numGamesMissed tinyint unsigned default 0;
  declare _userID int unsigned default 0;
  select if(_pick4!='',1,0) + if(_pick3!='',1,0) + if(_pick2!='',1,0) + if(_pick1!='',1,0) into _numPicks;
  select count(*) into _numGames from Game where weekNumber=_week and lockTime>now() and season=
         (select value from Constants where name='fetchSeason') and (homeTeam in 
         (_pick4, _pick3, _pick2, _pick1) or awayTeam in (_pick4, _pick3, _pick2, _pick1));
  select count(*) into _numGamesMissed from Game where weekNumber=_week and lockTime>now() and season=
         (select value from Constants where name='fetchSeason') and (homeTeam not in 
         (_pick4, _pick3, _pick2, _pick1) and awayTeam not in (_pick4, _pick3, _pick2, _pick1));
  select count(*) into _numGamesNeeded from Game where weekNumber=_week and lockTime>now() and season=
         (select value from Constants where name='fetchSeason');

  # make sure everything is set up correct-like
  if _numGames = _numGamesNeeded and _numPicks = _numGames and _numGamesMissed = 0 then
    # save the winners they picked
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick4, points=4 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick4 or awayTeam=_pick4);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick3, points=3 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick3 or awayTeam=_pick3);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick2, points=2 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick2 or awayTeam=_pick2);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick1, points=1 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick1 or awayTeam=_pick1);
    update PlayoffResult join Session using (userID) set tieBreaker1=_tieBreak1, tieBreaker2=_tieBreak2, 
      tieBreaker3=_tieBreak3, tieBreaker4=_tieBreak4 where sessionID=_sessID and IP=_IP and 
      season = (select value from Constants where name='fetchSeason') and weekNumber=_week;

    # add an event for it
    select userID into _userID from Session where sessionID=_sessID;
    insert into Event (userID, gameID, type, atTime) values (_userID, _week, 'madePicks', now());
  end if;
end;  //

create procedure SaveDivisionalPicks( in _sessID    int unsigned , 
                                      in _IP        varchar(100) ,
                                      in _tieBreak1 smallint     ,
                                      in _tieBreak2 smallint     ,
                                      in _tieBreak3 smallint     ,
                                      in _tieBreak4 smallint     ,
                                      in _pick4     char(3)      ,
                                      in _pick3     char(3)      ,
                                      in _pick2     char(3)      ,
                                      in _pick1     char(3)      )
begin
  # make sure theyve sent in the correct number of valid picks
  declare _week tinyint unsigned default 19;
  declare _numPicks tinyint unsigned default 0;
  declare _numGames tinyint unsigned default 0;
  declare _numGamesNeeded tinyint unsigned default 0;
  declare _numGamesMissed tinyint unsigned default 0;
  declare _userID int unsigned default 0;
  select if(_pick4!='',1,0) + if(_pick3!='',1,0) + if(_pick2!='',1,0) + if(_pick1!='',1,0) into _numPicks;
  select count(*) into _numGames from Game where weekNumber=_week and lockTime>now() and season=
         (select value from Constants where name='fetchSeason') and (homeTeam in 
         (_pick4, _pick3, _pick2, _pick1) or awayTeam in (_pick4, _pick3, _pick2, _pick1));
  select count(*) into _numGamesMissed from Game where weekNumber=_week and lockTime>now() and season=
         (select value from Constants where name='fetchSeason') and (homeTeam not in 
         (_pick4, _pick3, _pick2, _pick1) and awayTeam not in (_pick4, _pick3, _pick2, _pick1));
  select count(*) into _numGamesNeeded from Game where weekNumber=_week and lockTime>now() and season=
         (select value from Constants where name='fetchSeason');

  # make sure everything is set up correct-like
  if _numGames = _numGamesNeeded and _numPicks = _numGames and _numGamesMissed = 0 then
    # save the winners they picked
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick4, points=4 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick4 or awayTeam=_pick4);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick3, points=3 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick3 or awayTeam=_pick3);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick2, points=2 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick2 or awayTeam=_pick2);
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick1, points=1 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and (homeTeam=_pick1 or awayTeam=_pick1);
    update PlayoffResult join Session using (userID) set tieBreaker1=_tieBreak1, tieBreaker2=_tieBreak2, 
      tieBreaker3=_tieBreak3, tieBreaker4=_tieBreak4 where sessionID=_sessID and IP=_IP and 
      season = (select value from Constants where name='fetchSeason') and weekNumber=_week;

    # add an event for it
    select userID into _userID from Session where sessionID=_sessID;
    insert into Event (userID, gameID, type, atTime) values (_userID, _week, 'madePicks', now());
  end if;
end;  //

create procedure SaveConferencePicks( in _sessID    int unsigned , 
                                      in _IP        varchar(100) ,
                                      in _tieBreak1 smallint     ,
                                      in _tieBreak2 smallint     ,
                                      in _tieBreak3 smallint     ,
                                      in _tieBreak4 smallint     ,
                                      in _game4     int unsigned ,
                                      in _pick4     char(3)      ,
                                      in _type4     char(8)      ,
                                      in _game3     int unsigned ,
                                      in _pick3     char(3)      ,
                                      in _type3     char(8)      ,
                                      in _game2     int unsigned ,
                                      in _pick2     char(3)      ,
                                      in _type2     char(8)      ,
                                      in _game1     int unsigned ,
                                      in _pick1     char(3)      ,
                                      in _type1     char(8)      )
begin
  # make sure theyve sent in the correct number of valid picks
  declare _week tinyint unsigned default 20;
  declare _numPicks tinyint unsigned default 0;
  declare _numHalfs tinyint unsigned default 0;
  declare _numGames tinyint unsigned default 0;
  declare _numGamesNeeded tinyint unsigned default 0;
  declare _numGamesMissed tinyint unsigned default 0;
  declare _userID int unsigned default 0;
  select if(_pick4!='' and _type4='winner',1,0) + if(_pick3!='' and _type3='winner',1,0) + 
         if(_pick2!='' and _type2='winner',1,0) + if(_pick1!='' and _type1='winner',1,0) into _numPicks;
  select if(_pick4!='' and _type4='winner2Q',1,0) + if(_pick3!='' and _type3='winner2Q',1,0) + 
         if(_pick2!='' and _type2='winner2Q',1,0) + if(_pick1!='' and _type1='winner2Q',1,0) into _numHalfs;
  select count(*) into _numGames from Game where weekNumber=_week and lockTime>now() and season=
         (select value from Constants where name='fetchSeason') and (homeTeam in 
         (_pick4, _pick3, _pick2, _pick1) or awayTeam in (_pick4, _pick3, _pick2, _pick1));
  select count(*) into _numGamesMissed from Game where weekNumber=_week and lockTime>now() and season=
         (select value from Constants where name='fetchSeason') and (homeTeam not in 
         (_pick4, _pick3, _pick2, _pick1) and awayTeam not in (_pick4, _pick3, _pick2, _pick1));
  select count(*) into _numGamesNeeded from Game where weekNumber=_week and lockTime>now() and season=
         (select value from Constants where name='fetchSeason');

  # make sure everything is set up correct-like
  if _numGames = _numGamesNeeded and _numPicks = _numGames and _numHalfs = _numPicks and _numGamesMissed = 0 then
    # save the winners they picked
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick4, points=4 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and gameID=_game4 and type=_type4;
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick3, points=3 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and gameID=_game3 and type=_type3;
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick2, points=2 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and gameID=_game2 and type=_type2;
    update Pick join Session using (userID) join Game using (gameID) set winner=_pick1, points=1 
      where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
            weekNumber=_week and lockTime>=now() and gameID=_game1 and type=_type1;
    update PlayoffResult join Session using (userID) set tieBreaker1=_tieBreak1, tieBreaker2=_tieBreak2, 
      tieBreaker3=_tieBreak3, tieBreaker4=_tieBreak4 where sessionID=_sessID and IP=_IP and 
      season = (select value from Constants where name='fetchSeason') and weekNumber=_week;

    # add an event for it
    select userID into _userID from Session where sessionID=_sessID;
    insert into Event (userID, gameID, type, atTime) values (_userID, _week, 'madePicks', now());
  end if;
end;  //

create procedure SaveSuperBowlPicks( in _sessID    int unsigned , 
                                     in _IP        varchar(100) ,
                                     in _tieBreak1 smallint     ,
                                     in _winner    char(3)      ,
                                     in _winner3Q  char(3)      ,
                                     in _winner2Q  char(3)      ,
                                     in _winner1Q  char(3)      ,
                                     in _passYds   char(3)      ,
                                     in _passYds2Q char(3)      ,
                                     in _rushYds   char(3)      ,
                                     in _rushYds2Q char(3)      ,
                                     in _TDs       char(3)      ,
                                     in _TDs2Q     char(3)      )
begin
  # make sure theyve sent in the correct number of valid picks
  declare _week tinyint unsigned default 22;
  declare _userID int unsigned default 0;
  declare _gameID int unsigned default 0;
  
  # grab the super bowl gameID
  select gameID into _gameID from Game where weekNumber=_week and season = (select value from Constants where name='fetchSeason');

  # save the winners they picked
  update Pick join Session using (userID) join Game using (gameID) set winner=_winner
    where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
          weekNumber=_week and lockTime>=now() and gameID=_gameID and type='winner';
  update Pick join Session using (userID) join Game using (gameID) set winner=_winner3Q
    where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
          weekNumber=_week and lockTime>=now() and gameID=_gameID and type='winner3Q';
  update Pick join Session using (userID) join Game using (gameID) set winner=_winner2Q
    where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
          weekNumber=_week and lockTime>=now() and gameID=_gameID and type='winner2Q';
  update Pick join Session using (userID) join Game using (gameID) set winner=_winner1Q
    where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
          weekNumber=_week and lockTime>=now() and gameID=_gameID and type='winner1Q';
  update Pick join Session using (userID) join Game using (gameID) set winner=_passYds
    where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
          weekNumber=_week and lockTime>=now() and gameID=_gameID and type='passYds';
  update Pick join Session using (userID) join Game using (gameID) set winner=_passYds2Q
    where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
          weekNumber=_week and lockTime>=now() and gameID=_gameID and type='passYds2Q';
  update Pick join Session using (userID) join Game using (gameID) set winner=_rushYds
    where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
          weekNumber=_week and lockTime>=now() and gameID=_gameID and type='rushYds';
  update Pick join Session using (userID) join Game using (gameID) set winner=_rushYds2Q
    where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
          weekNumber=_week and lockTime>=now() and gameID=_gameID and type='rushYds2Q';
  update Pick join Session using (userID) join Game using (gameID) set winner=_TDs
    where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
          weekNumber=_week and lockTime>=now() and gameID=_gameID and type='TDs';
  update Pick join Session using (userID) join Game using (gameID) set winner=_TDs2Q
    where sessionID=_sessID and IP=_IP and season = (select value from Constants where name='fetchSeason') and 
          weekNumber=_week and lockTime>=now() and gameID=_gameID and type='TDs2Q';
  update PlayoffResult join Session using (userID) set tieBreaker1=_tieBreak1 where sessionID=_sessID and IP=_IP and 
    season = (select value from Constants where name='fetchSeason') and weekNumber=_week;

  # add an event for it
  select userID into _userID from Session where sessionID=_sessID;
  insert into Event (userID, gameID, type, atTime) values (_userID, _week, 'madePicks', now());
end;  //

create procedure EditAccount( in _sessID     int unsigned , 
                              in _uname      varchar(36)  , 
                              in _email      varchar(200) ,
                              in _password   char(32)     )
begin
  declare _userID int unsigned default 0;

  # edit this guys info
  update User join Session using (userID) 
    set username=if(_uname is not null, _uname, username), 
        email=if(_email is not null, _email, email), 
        password=if(_password is not null, _password, password) 
    where sessionID=_sessID;

  # add the event
  select userID into _userID from Session where sessionID=_sessID;
  insert into Event (userID, type, atTime) values (_userID, 'userEdited', now());
end;  //





##### StevePoolAdmin functions
create procedure AddUser( in _uname      varchar(36)  , 
                          in _email      varchar(200) ,
                          in _password   varchar(100) ,
                          in _firstName  varchar(36)  ,
                          in _lastName   varchar(36)  ) 
begin
  declare _userID int unsigned default 0;
  declare _season smallint;
  declare _week tinyint default 1;

  # punch in the new guy
  insert into User (username, email, password, firstName, lastName) 
            values (_uname, _email, md5(_password), _firstName, _lastName);

  # grab the new id
  select userID into _userID from User where username=_uname;

  # fill in his results and give him all the null picks
  call AddUserToSeason(_userID);
end;  //

create procedure EditUser( in _userID     int unsigned     , 
                           in _uname      varchar(36)      , 
                           in _email      varchar(200)     ,
                           in _password   char(32)         ,
                           in _firstName  varchar(36)      ,
                           in _lastName   varchar(36)      ,
                           in _divID      tinyint unsigned ) 
begin
  # edit this guys info
  update User set username=if(_uname is not null, _uname, username), 
                  email=if(_email is not null, _email, email), 
                  password=if(_password is not null, _password, password), 
                  firstName=if(_firstName is not null, _firstName, firstName),
                  lastName=if(_lastName is not null, _lastName, lastName) 
              where userID=_userID;

  # fix his division
  call AssignToDivision(_userID, _divID);

  # add the event
  insert into Event (userID, type, atTime) values (_userID, 'userEdited', now());
end;  //

create procedure AddUserToSeason( in _userID int unsigned )
begin
  declare _season smallint;
  declare _week tinyint;

  # fill in his results and give him all the null picks
  select cast(value as signed) into _season from Constants where name='fetchSeason';
  select cast(value as signed) into _week from Constants where name='fetchWeek';
  insert into SeasonResult values (_userID, _season, null, 0, 0, 0, 0, 'R', 'R');
  while _week < 18 do
    insert into WeekResult values (_userID, _season, _week, 0, 0);
    insert into Pick (userID, gameID, points) select userID, gameID, @rowNum := @rowNum - 1 
      from User join Game, (select @rowNum := 17) r 
      where userID=_userID and season=_season and weekNumber=_week order by gameTime asc, gameID asc;
    set _week = _week + 1;
  end while;

  # add the event
  insert into Event (userID, type, atTime) values (_userID, 'userAdded', now());
end;  //

create procedure RemoveUserFromSeason( in _userID int unsigned )
begin
  declare _season smallint;
  declare _week tinyint;

  # wipe his results and picks
  select cast(value as signed) into _season from Constants where name='fetchSeason';
  select cast(value as signed) into _week from Constants where name='fetchWeek';
  delete from SeasonResult where userID=_userID and season=_season;
  delete from WeekResult where userID=_userID and season=_season and weekNumber>=_week;
  delete from Pick where userID=_userID and gameID in (select gameID from Game where season=_season and weekNumber>=_week);

  # add the event
  insert into Event (userID, type, atTime) values (_userID, 'userRemoved', now());
end;  //

create procedure AddConference( in _name varchar(200) )
begin
  # add it
  insert into Conference (name) values (_name);
end;  //

create procedure AddDivision( in _name   varchar(200)     ,
                              in _confID tinyint unsigned )
begin
  # add it
  insert into Division (name, confID) values (_name, _confID);
end;  //

create procedure AssignToDivision( in _userID int unsigned     ,
                                   in _divID  tinyint unsigned )
begin
  declare _season smallint;

  # add it
  select cast(value as signed) into _season from Constants where name='fetchSeason';
  update SeasonResult set divID=_divID where userID=_userID and season=_season;
end;  //

create procedure ChangeLockTime( in _gameID  int unsigned ,
                                 in _newLock datetime     )
begin
  # change it
  update Game set lockTime=_newLock where gameID=_gameID;

  # add the event
  insert into Event (gameID, type, atTime) values (_gameID, 'lockChange', now());
end;  //

create procedure AdminSetPicks( in _userID   int unsigned , 
                                in _gameID16 int unsigned ,
                                in _pick16   char(3)      ,
                                in _gameID15 int unsigned ,
                                in _pick15   char(3)      ,
                                in _gameID14 int unsigned ,
                                in _pick14   char(3)      ,
                                in _gameID13 int unsigned ,
                                in _pick13   char(3)      ,
                                in _gameID12 int unsigned ,
                                in _pick12   char(3)      ,
                                in _gameID11 int unsigned ,
                                in _pick11   char(3)      ,
                                in _gameID10 int unsigned ,
                                in _pick10   char(3)      ,
                                in _gameID9  int unsigned ,
                                in _pick9    char(3)      ,
                                in _gameID8  int unsigned ,
                                in _pick8    char(3)      ,
                                in _gameID7  int unsigned ,
                                in _pick7    char(3)      ,
                                in _gameID6  int unsigned ,
                                in _pick6    char(3)      ,
                                in _gameID5  int unsigned ,
                                in _pick5    char(3)      ,
                                in _gameID4  int unsigned ,
                                in _pick4    char(3)      ,
                                in _gameID3  int unsigned ,
                                in _pick3    char(3)      ,
                                in _gameID2  int unsigned ,
                                in _pick2    char(3)      ,
                                in _gameID1  int unsigned ,
                                in _pick1    char(3)      ,
                                in _tieBreak smallint     )
begin
  # make sure theyve sent in valid picks
  declare _numPicks tinyint unsigned default 0;
  declare _numGamesPicked tinyint unsigned default 0;
  declare _numGamesBlank tinyint unsigned default 0;
  declare _numWeeks tinyint unsigned default 0;
  select if(_gameID16!=0,1,0) + if(_gameID15!=0,1,0) + if(_gameID14!=0,1,0) + if(_gameID13!=0,1,0) + 
         if(_gameID12!=0,1,0) + if(_gameID11!=0,1,0) + if(_gameID10!=0,1,0) + if(_gameID9!=0,1,0) + 
         if(_gameID8!=0,1,0) + if(_gameID7!=0,1,0) + if(_gameID6!=0,1,0) + if(_gameID5!=0,1,0) + 
         if(_gameID4!=0,1,0) + if(_gameID3!=0,1,0) + if(_gameID2!=0,1,0) + if(_gameID1!=0,1,0) into _numPicks;
  select if(_pick16='' and _gameID16!=0,1,0) + if(_pick15='' and _gameID15!=0,1,0) + 
         if(_pick14='' and _gameID14!=0,1,0) + if(_pick13='' and _gameID13!=0,1,0) + 
         if(_pick12='' and _gameID12!=0,1,0) + if(_pick11='' and _gameID11!=0,1,0) +
         if(_pick10='' and _gameID10!=0,1,0) + if(_pick9='' and _gameID9!=0,1,0) + 
         if(_pick8='' and _gameID8!=0,1,0) + if(_pick7='' and _gameID7!=0,1,0) + 
         if(_pick6='' and _gameID6!=0,1,0) + if(_pick5='' and _gameID5!=0,1,0) + 
         if(_pick4='' and _gameID4!=0,1,0) + if(_pick3='' and _gameID3!=0,1,0) + 
         if(_pick2='' and _gameID2!=0,1,0) + if(_pick1='' and _gameID1!=0,1,0) into _numGamesBlank;
  select count(*) into _numGamesPicked from Game where season=(select value from Constants where name='fetchSeason') 
         and (homeTeam in 
         (_pick16, _pick15, _pick14, _pick13, _pick12, _pick11, _pick10, _pick9,
          _pick8, _pick7, _pick6, _pick5, _pick4, _pick3, _pick2, _pick1) or awayTeam in 
         (_pick16, _pick15, _pick14, _pick13, _pick12, _pick11, _pick10, _pick9,
          _pick8, _pick7, _pick6, _pick5, _pick4, _pick3, _pick2, _pick1)) and gameID in 
         (_gameID16, _gameID15, _gameID14, _gameID13, _gameID12, _gameID11, _gameID10, _gameID9, 
          _gameID8, _gameID7, _gameID6, _gameID5, _gameID4, _gameID3, _gameID2, _gameID1);
  select count(distinct weekNumber) into _numWeeks from Game where gameID in 
         (_gameID16, _gameID15, _gameID14, _gameID13, _gameID12, _gameID11, _gameID10, _gameID9, 
          _gameID8, _gameID7, _gameID6, _gameID5, _gameID4, _gameID3, _gameID2, _gameID1);

  # make sure everything is set up correct-like
  if _numPicks = (_numGamesPicked + _numGamesBlank) and _numWeeks = 1 then
    # save the winners they picked
    update Pick set winner=if(_pick16='', null, _pick16), points=16 where userID=_userID and gameID=_gameID16;
    update Pick set winner=if(_pick15='', null, _pick15), points=15 where userID=_userID and gameID=_gameID15;
    update Pick set winner=if(_pick14='', null, _pick14), points=14 where userID=_userID and gameID=_gameID14;
    update Pick set winner=if(_pick13='', null, _pick13), points=13 where userID=_userID and gameID=_gameID13;
    update Pick set winner=if(_pick12='', null, _pick12), points=12 where userID=_userID and gameID=_gameID12;
    update Pick set winner=if(_pick11='', null, _pick11), points=11 where userID=_userID and gameID=_gameID11;
    update Pick set winner=if(_pick10='', null, _pick10), points=10 where userID=_userID and gameID=_gameID10;
    update Pick set winner=if(_pick9='', null, _pick9), points=9 where userID=_userID and gameID=_gameID9;
    update Pick set winner=if(_pick8='', null, _pick8), points=8 where userID=_userID and gameID=_gameID8;
    update Pick set winner=if(_pick7='', null, _pick7), points=7 where userID=_userID and gameID=_gameID7;
    update Pick set winner=if(_pick6='', null, _pick6), points=6 where userID=_userID and gameID=_gameID6;
    update Pick set winner=if(_pick5='', null, _pick5), points=5 where userID=_userID and gameID=_gameID5;
    update Pick set winner=if(_pick4='', null, _pick4), points=4 where userID=_userID and gameID=_gameID4;
    update Pick set winner=if(_pick3='', null, _pick3), points=3 where userID=_userID and gameID=_gameID3;
    update Pick set winner=if(_pick2='', null, _pick2), points=2 where userID=_userID and gameID=_gameID2;
    update Pick set winner=if(_pick1='', null, _pick1), points=1 where userID=_userID and gameID=_gameID1;

    # fix the tiebreaker
    update WeekResult join Game using (weekNumber) set tieBreaker=_tieBreak where userID=_userID and gameID=_gameID16;

    # add an event for it
    select weekNumber into _numWeeks from Game where gameID in 
           (_gameID16, _gameID15, _gameID14, _gameID13, _gameID12, _gameID11, _gameID10, _gameID9, 
            _gameID8, _gameID7, _gameID6, _gameID5, _gameID4, _gameID3, _gameID2, _gameID1) limit 1;
    insert into Event (userID, gameID, type, atTime) values (_userID, _numWeeks, 'picksEdited', now());
  end if;
end;  //





##### basic user permissions   
##### production pword: $t3v3P00lU$3r
revoke all privileges, grant option from 'StevePoolUser'@'localhost';  //
grant select on StevePool.* to 'StevePoolUser'@'localhost' identified by '$t3v3P00lU$3r';  //
grant execute on procedure StevePool.Login to 'StevePoolUser'@'localhost';  //
grant execute on procedure StevePool.Logout to 'StevePoolUser'@'localhost';  //
grant execute on procedure StevePool.SavePicks to 'StevePoolUser'@'localhost';  //
grant execute on procedure StevePool.SaveConsolationPicks to 'StevePoolUser'@'localhost';  //
grant execute on procedure StevePool.SaveWildCardPicks to 'StevePoolUser'@'localhost';  //
grant execute on procedure StevePool.SaveDivisionalPicks to 'StevePoolUser'@'localhost';  //
grant execute on procedure StevePool.SaveConferencePicks to 'StevePoolUser'@'localhost';  //
grant execute on procedure StevePool.SaveSuperBowlPicks to 'StevePoolUser'@'localhost';  //
grant execute on procedure StevePool.EditAccount to 'StevePoolUser'@'localhost';  //

##### admin permissions   
##### production pword: $t3v3P00l4dm!n
revoke all privileges, grant option from 'StevePoolAdmin'@'localhost';  //
grant select on StevePool.* to 'StevePoolAdmin'@'localhost' identified by '$t3v3P00l4dm!n';  //
grant execute on procedure StevePool.AddUser to 'StevePoolAdmin'@'localhost';  //
grant execute on procedure StevePool.EditUser to 'StevePoolAdmin'@'localhost';  //
grant execute on procedure StevePool.AddUserToSeason to 'StevePoolAdmin'@'localhost';  //
grant execute on procedure StevePool.RemoveUserFromSeason to 'StevePoolAdmin'@'localhost';  //
grant execute on procedure StevePool.AddConference to 'StevePoolAdmin'@'localhost';  //
grant execute on procedure StevePool.AddDivision to 'StevePoolAdmin'@'localhost';  //
grant execute on procedure StevePool.AssignToDivision to 'StevePoolAdmin'@'localhost';  //
grant execute on procedure StevePool.ChangeLockTime to 'StevePoolAdmin'@'localhost';  //
grant execute on procedure StevePool.AdminSetPicks to 'StevePoolAdmin'@'localhost';  //


delimiter ;
