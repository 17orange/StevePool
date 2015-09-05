import statsUtil
import fetchWeek
from subprocess import Popen

# grab the cursor from the util file
cur = statsUtil.cur

# see what season we want to grab
cur.execute("select value from Constants where name='fetchSeason'")
season = int(cur.fetchall()[0][0])

# wipe the games
cur.execute("delete from Game where season=" + str(season))

# grab the requested season's games
for i in range(17):
	fetchWeek.GrabWeekGames(str(i+1), str(season))

# set the lock times to be 5 minutes before the earliest game that day (or earliest game on sunday if this is a monday game)
cur.execute("create temporary table LockTimes (gameID int unsigned primary key, lTime datetime)")
cur.execute("select gameID, (select date_add(min(gameTime), interval -5 minute) from Game as G2 where date(G2.gameTime)=date(date_add(G1.gameTime, interval if(date_format(G1.gameTime, '%a')='Mon', -1, 0) day))) from Game as G1")
cur.execute("insert into LockTimes select gameID, (select date_add(min(gameTime), interval -5 minute) from Game as G2 where date(G2.gameTime)=date(date_add(G1.gameTime, interval if(date_format(G1.gameTime, '%a')='Mon', -1, 0) day))) from Game as G1")
cur.execute("update Game join LockTimes using (gameID) set lockTime=lTime")
cur.execute("drop table LockTimes")

# mark the latest update
cur.execute("update Constants set value='1' where name='fetchWeek'")

# commit the changes
statsUtil.db.commit()
