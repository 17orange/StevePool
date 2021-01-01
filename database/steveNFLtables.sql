### clear the tables
drop table if exists Constants;
drop table if exists User;
drop table if exists Team;
drop table if exists Game;
drop table if exists Pick;
drop table if exists WeekResult;
drop table if exists SeasonResult;
drop table if exists Conference;
drop table if exists Division;
drop table if exists Event;
drop table if exists Session;
drop table if exists ConsolationResult;
drop table if exists PlayoffResult;


### create all the tables
create table Constants (name varchar(200) primary key,
                        value varchar(200));

create table User (userID int unsigned not null primary key auto_increment, 
                   username varchar(36) not null unique, 
                   password char(32) not null, 
                   firstName varchar(36) not null, 
                   lastName varchar(36) not null, 
                   email varchar(200) not null unique);

create table Team (teamID char(3) not null primary key, 
                   alias char(3) not null, 
                   city varchar(36) not null, 
                   nickname varchar(36) not null,
                   isActive enum('Y','N') not null default 'Y');

create table Game (gameID int unsigned not null primary key auto_increment, 
                   season smallint not null, 
                   weekNumber tinyint not null, 
                   tieBreakOrder tinyint not null default 0, 
                   gameTime datetime not null,
                   lockTime datetime not null,
                   status tinyint not null,
                   homeTeam char(3) not null, 
                   homeScore tinyint not null default 0, 
                   awayTeam char(3) not null,
                   awayScore tinyint not null default 0, 
                   timeLeft varchar(200),
                   homeScore1Q tinyint not null default 0,
                   awayScore1Q tinyint not null default 0,
                   homeScore2Q tinyint not null default 0,
                   awayScore2Q tinyint not null default 0,
                   homeScore3Q tinyint not null default 0,
                   awayScore3Q tinyint not null default 0,
                   homePassYds smallint not null default 0,
                   awayPassYds smallint not null default 0,
                   homePassYds2Q smallint not null default 0,
                   awayPassYds2Q smallint not null default 0,
                   homeRushYds smallint not null default 0,
                   awayRushYds smallint not null default 0,
                   homeRushYds2Q smallint not null default 0,
                   awayRushYds2Q smallint not null default 0,
                   homePassYds smallint not null default 0,
                   awayPassYds smallint not null default 0,
                   homeTDs tinyint not null default 0,
                   awayTDs tinyint not null default 0,
                   homeTDs2Q tinyint not null default 0,
                   awayTDs2Q tinyint not null default 0,
                   NFLgameID tinyint unsigned not null default 0);
alter table Game add index timeIndex (season, weekNumber);
alter table Game add index statusIndex (status);

create table Pick (userID int unsigned not null,
                   gameID int unsigned not null,
                   type enum('winner','winner3Q','winner2Q','winner1Q','passYds','passYds2Q','rushYds','rushYds2Q','TDs','TDs2Q') not null default 'winner',
                   points smallint not null,
                   winner char(3));
alter table Pick add primary key (userID, gameID, type);

create table WeekResult (userID int unsigned not null,
                         season smallint not null, 
                         weekNumber tinyint not null,
                         tieBreaker smallint not null default 0, 
                         points smallint unsigned);
alter table WeekResult add primary key (userID, season, weekNumber);
alter table WeekResult add index timeIndex (season, weekNumber);

create table SeasonResult (userID int unsigned not null,
                           season smallint not null, 
                           divID tinyint unsigned, 
                           points smallint unsigned,
                           weeklyWins tinyint unsigned,
                           missedWeeks tinyint unsigned,
                           correctPicks smallint unsigned,
                           inPlayoffs enum('Y', 'N', 'R') default 'R',
                           firstRoundBye enum('Y', 'N', 'R') default 'R');
alter table SeasonResult add primary key (userID, season);
alter table SeasonResult add index timeIndex (season);

create table Conference (confID tinyint unsigned primary key auto_increment,
                         name varchar(200) not null);

create table Division (divID tinyint unsigned primary key auto_increment,
                       confID tinyint unsigned not null,
                       name varchar(200) not null);

create table Event (eventID int unsigned primary key auto_increment,
                    userID int unsigned,
                    gameID int unsigned,
                    type enum('userAdded', 'userEdited', 'userRemoved', 'login', 'madePicks', 'picksEdited', 
                              'timeChange', 'lockChange', 'adminPicksEdited', 'accountChanged', 'forgotPassword') not null,
                    atTime datetime not null,
                    browserInfo varchar(255));

create table Session(sessionID int unsigned primary key auto_increment,
                     userID int unsigned not null,
                     IP varchar(100) not null);

create table ConsolationResult (userID int unsigned not null, 
                                season smallint not null,
                                points tinyint unsigned not null default 0,
                                wc1AFC char(3),
                                wc2AFC char(3),
                                wc3AFC char(3),
                                wc1NFC char(3),
                                wc2NFC char(3),
                                wc3NFC char(3),
                                div1AFC char(3),
                                div2AFC char(3),
                                div1NFC char(3),
                                div2NFC char(3),
                                confAFC char(3),
                                confNFC char(3),
                                superBowl char(3),
                                picksCorrect tinyint not null default 0);
                                tieBreaker smallint not null default 0);
alter table ConsolationResult add primary key (userID, season);

create table PlayoffResult (userID int unsigned not null,
                            season smallint not null,
                            weekNumber tinyint not null,
                            points tinyint unsigned not null default 0,
                            tieBreaker1 smallint not null default 0,
                            tieBreaker2 smallint not null default 0,
                            tieBreaker3 smallint not null default 0,
                            tieBreaker4 smallint not null default 0,
                            tieBreaker5 smallint not null default 0,
                            tieBreaker6 smallint not null default 0,
                            advances enum ('Y', 'N', 'R') not null default 'N',
                            prevWeek1 tinyint unsigned not null default 0,
                            prevWeek2 tinyint unsigned not null default 0,
                            prevWeek3 tinyint unsigned not null default 0);
alter table PlayoffResult add primary key (userID, season, weekNumber);



insert into Team values ("ARI", "ARZ", "Arizona",       "Cardinals",     "Y");
insert into Team values ("ATL", "ATL", "Atlanta",       "Falcons",       "Y");
insert into Team values ("BAL", "BAL", "Baltimore",     "Ravens",        "Y");
insert into Team values ("BUF", "BUF", "Buffalo",       "Bills",         "Y");
insert into Team values ("CAR", "CAR", "Carolina",      "Panthers",      "Y");
insert into Team values ("CHI", "CHI", "Chicago",       "Bears",         "Y");
insert into Team values ("CIN", "CIN", "Cincinnati",    "Bengals",       "Y");
insert into Team values ("CLE", "CLE", "Cleveland",     "Browns",        "Y");
insert into Team values ("DAL", "DAL", "Dallas",        "Cowboys",       "Y");
insert into Team values ("DEN", "DEN", "Denver",        "Broncos",       "Y");
insert into Team values ("DET", "DET", "Detroit",       "Lions",         "Y");
insert into Team values ("GB",  "GB",  "Green Bay",     "Packers",       "Y");
insert into Team values ("HOU", "HOU", "Houston",       "Texans",        "Y");
insert into Team values ("IND", "IND", "Indianapolis",  "Colts",         "Y");
insert into Team values ("JAC", "JAC", "Jacksonville",  "Jaguars",       "N");
insert into Team values ("JAX", "JAX", "Jacksonville",  "Jaguars",       "Y");
insert into Team values ("KC",  "KC",  "Kansas City",   "Chiefs",        "Y");
insert into Team values ("LA",  "LAR", "Los Angeles",   "Rams",          "Y");
insert into Team values ("LAC", "LAC", "Los Angeles",   "Chargers",      "Y");
insert into Team values ("LV",  "LV",  "Las Vegas",     "Raiders",       "Y");
insert into Team values ("MIA", "MIA", "Miami",         "Dolphins",      "Y");
insert into Team values ("MIN", "MIN", "Minnesota",     "Vikings",       "Y");
insert into Team values ("NE",  "NE",  "New England",   "Patriots",      "Y");
insert into Team values ("NO",  "NO",  "New Orleans",   "Saints",        "Y");
insert into Team values ("NYG", "NYG", "New York",      "Giants",        "Y");
insert into Team values ("NYJ", "NYJ", "New York",      "Jets",          "Y");
insert into Team values ("OAK", "OAK", "Oakland",       "Raiders",       "N");
insert into Team values ("PHI", "PHI", "Philadelphia",  "Eagles",        "Y");
insert into Team values ("PIT", "PIT", "Pittsburgh",    "Steelers",      "Y");
insert into Team values ("SD",  "SD",  "San Diego",     "Chargers",      "N");
insert into Team values ("SEA", "SEA", "Seattle",       "Seahawks",      "Y");
insert into Team values ("SF",  "SF",  "San Fransisco", "49ers",         "Y");
insert into Team values ("STL", "STL", "St. Louis",     "Rams",          "N");
insert into Team values ("TB",  "TB",  "Tampa Bay",     "Buccaneers",    "Y");
insert into Team values ("TEN", "TEN", "Tennessee",     "Titans",        "Y");
insert into Team values ("WAS", "WAS", "Washington",    "Football Team", "Y");

insert into Constants values ("fetchSeason", "2014");
insert into Constants values ("fetchWeek", "1");

