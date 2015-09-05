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
                   city varchar(36) not null, 
                   nickname varchar(36) not null);

create table Game (gameID int unsigned not null primary key auto_increment, 
                   season smallint not null, 
                   weekNumber tinyint not null, 
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
                              'lockChange', 'adminPicksEdited', 'accountChanged') not null,
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
                                wc1NFC char(3),
                                wc2NFC char(3),
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
                            advances enum ('Y', 'N', 'R') not null default 'N',
                            prevWeek1 tinyint unsigned not null default 0,
                            prevWeek2 tinyint unsigned not null default 0,
                            prevWeek3 tinyint unsigned not null default 0);
alter table PlayoffResult add primary key (userID, season, weekNumber);



insert into Team values ("ARI", "Arizona",       "Cardinals");
insert into Team values ("ATL", "Atlanta",       "Falcons");
insert into Team values ("BAL", "Baltimore",     "Ravens");
insert into Team values ("BUF", "Buffalo",       "Bills");
insert into Team values ("CAR", "Carolina",      "Panthers");
insert into Team values ("CHI", "Chicago",       "Bears");
insert into Team values ("CIN", "Cincinnati",    "Bengals");
insert into Team values ("CLE", "Cleveland",     "Browns");
insert into Team values ("DAL", "Dallas",        "Cowboys");
insert into Team values ("DEN", "Denver",        "Broncos");
insert into Team values ("DET", "Detroit",       "Lions");
insert into Team values ("GB",  "Green Bay",     "Packers");
insert into Team values ("HOU", "Houston",       "Texans");
insert into Team values ("IND", "Indianapolis",  "Colts");
insert into Team values ("JAX", "Jacksonville",  "Jaguars");
insert into Team values ("KC",  "Kansas City",   "Chiefs");
insert into Team values ("MIA", "Miami",         "Dolphins");
insert into Team values ("MIN", "Minnesota",     "Vikings");
insert into Team values ("NE",  "New England",   "Patriots");
insert into Team values ("NO",  "New Orleans",   "Saints");
insert into Team values ("NYG", "New York",      "Giants");
insert into Team values ("NYJ", "New York",      "Jets");
insert into Team values ("OAK", "Oakland",       "Raiders");
insert into Team values ("PHI", "Philadelphia",  "Eagles");
insert into Team values ("PIT", "Pittsburgh",    "Steelers");
insert into Team values ("SD",  "San Diego",     "Chargers");
insert into Team values ("SEA", "Seattle",       "Seahawks");
insert into Team values ("SF",  "San Fransisco", "49ers");
insert into Team values ("STL", "St. Louis",     "Rams");
insert into Team values ("TB",  "Tampa Bay",     "Buccaneers");
insert into Team values ("TEN", "Tennessee",     "Titans");
insert into Team values ("WAS", "Washington",    "Redskins");

insert into Constants values ("fetchSeason", "2014");
insert into Constants values ("fetchWeek", "1");

