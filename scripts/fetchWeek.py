import urllib
import statsUtil
import datetime
import sys
from subprocess import call

# get the util cursor
cur = statsUtil.cur
allDone = True

# function to gather this info
def GrabWeekGames(week, season):
	# grab the live scores from the nfl
	liveScores = urllib.urlopen("http://www.nfl.com/liveupdate/scores/scores.json").read()

	# parse through it to find scores
	homeScores = {}
	homeScores1Q = {}
	homeScores2Q = {}
	homeScores3Q = {}
	awayScores = {}
	awayScores1Q = {}
	awayScores2Q = {}
	awayScores3Q = {}
	homeTimes = {}
	start = liveScores.find('"home"')
	while( start != -1 ):
		start = liveScores.find('"1":', start) + 4
		scoreEnd = liveScores.find(',', start)
		homeScore1Q = liveScores[start:scoreEnd]

		start = liveScores.find('"2":', start) + 4
		scoreEnd = liveScores.find(',', start)
		homeScore2Q = liveScores[start:scoreEnd]

		start = liveScores.find('"3":', start) + 4
		scoreEnd = liveScores.find(',', start)
		homeScore3Q = liveScores[start:scoreEnd]

		start = liveScores.find('"T":', start) + 4
		scoreEnd = liveScores.find('}', start)
		homeScore = liveScores[start:scoreEnd]

		start = liveScores.find('"abbr":"', start) + 8
		teamEnd = liveScores.find('"', start)
		homeTeam = liveScores[start:teamEnd]
		if( homeTeam == "JAC" ):
			homeTeam = "JAX"

		start = liveScores.find('"1":', start) + 4
		scoreEnd = liveScores.find(',', start)
		awayScore1Q = liveScores[start:scoreEnd]

		start = liveScores.find('"2":', start) + 4
		scoreEnd = liveScores.find(',', start)
		awayScore2Q = liveScores[start:scoreEnd]

		start = liveScores.find('"3":', start) + 4
		scoreEnd = liveScores.find(',', start)
		awayScore3Q = liveScores[start:scoreEnd]

		start = liveScores.find('"T":', start) + 4
		scoreEnd = liveScores.find('}', start)
		awayScore = liveScores[start:scoreEnd]

		start = liveScores.find('"abbr":"', start) + 8
		teamEnd = liveScores.find('"', start)
		awayTeam = liveScores[start:teamEnd]
		if( awayTeam == "JAC" ):
			awayTeam = "JAX"

#		start = liveScores.find('"qtr":', start) + 6
#		if liveScores[start:(start+4)] != "null":
#			qtrEnd = liveScores.find('"', start)
#			timeLeft = liveScores[start:qtrEnd]
#			if (start + 1) == qtrEnd:
#				timeLeft = "Q" + timeLeft
#
#				start = liveScores.find('"clock":"', start) + 9
#				timeEnd = liveScores.find('"', start)
#				timeLeft = timeLeft + " " + liveScores[start:timeEnd]
#		else:
#			timeLeft = ""

		start = liveScores.find('"clock":', start) + 9
		if liveScores[start:(start+4)] != "null" :
			timeEnd = liveScores.find('"', start)
			timeLeft = liveScores[start:timeEnd]

			start = liveScores.find('"qtr":', start) + 6
			if liveScores[(start+2):(start+9)] == "alftime":
				timeLeft = "Halftime"
			elif liveScores[(start+2):(start+6)] == "inal":
				timeLeft = "FINAL"
			elif liveScores[start:(start+4)] != "null" and liveScores[(start+1):(start+8)] != "Pregame":
				qtrEnd = liveScores.find('"', (start + 1))
				timeLeft = "Q" + liveScores[(start + 1):qtrEnd] + " " + timeLeft
			else:
				timeLeft = ""
		else:
			timeLeft = ""

		homeScores[homeTeam] = homeScore
		homeScores1Q[homeTeam] = homeScore1Q
		homeScores2Q[homeTeam] = homeScore2Q
		homeScores3Q[homeTeam] = homeScore3Q
		awayScores[awayTeam] = awayScore
		awayScores1Q[awayTeam] = awayScore1Q
		awayScores2Q[awayTeam] = awayScore2Q
		awayScores3Q[awayTeam] = awayScore3Q
		homeTimes[homeTeam] = timeLeft

		start = liveScores.find('"home"', start + 1)

	# grab the requested week's games
	#pageUrl = "http://www.nfl.com/scores/" + season + "/"
	pageUrl = "http://www.nfl.com/ajax/scorestrip?season=" + season + "&seasonType="
	if( int(week) < 18 ):
		pageUrl = pageUrl + "REG"
	else:
		pageUrl = pageUrl + "POST"
	pageUrl = pageUrl + "&week=" + week
	fullPage = urllib.urlopen(pageUrl).read()

	# get the start position of the first game
	start = fullPage.find('<g eid="')
	allDone = True
	while( start != -1 ):
		# see where the next game starts
		next = fullPage.find('<g eid="', start + 1)

		# get the game date
		day = start + 8
		dayEnd = day + 8
		day = fullPage[day:dayEnd]

		# get the nfl id
		nflID = int(fullPage[dayEnd:(dayEnd+2)])

                # get the quarter
		qrtr = fullPage.find(' q="', start) + 4
		qrtrEnd = fullPage.find('"', qrtr)
		quarter = fullPage[qrtr:qrtrEnd]

		# get the home team
		home = fullPage.find(' h="', start) + 4
	 	homeEnd = fullPage.find('"', home)
		homeTeam = fullPage[home:homeEnd]

		# get the time left
		if( quarter == "F" or quarter == "FO" ):
			timeLeft = "FINAL"
		else:
			timeLeft = homeTimes[homeTeam]
			###timeLeft = ""
			clk = fullPage.find(' t="', start) + 4
			clkEnd = fullPage.find('"', clk)
			clock = fullPage[clk:clkEnd]

		# get the home score
		score = fullPage.find(' hs="', home) + 5
		scoreEnd = fullPage.find('"', score)
		homeScore = fullPage[score:scoreEnd]
		if (timeLeft == ""):
			homeScore = 0
		else:
			homeScore = homeScores[homeTeam]

		# see whether they have the quarterly breakdown yet
		if( homeScore == 0 ):
			home1QScore = 0;
			home2QScore = 0;
			home3QScore = 0;
		else:
			home1QScore = int(homeScores1Q[homeTeam]);
			home2QScore = home1QScore + int(homeScores2Q[homeTeam]);
			home3QScore = home2QScore + int(homeScores3Q[homeTeam]);

		# get the away team
		away = fullPage.find(' v="', start) + 4
		awayEnd = fullPage.find('"', away)
		awayTeam = fullPage[away:awayEnd]

		# get the away score
		score = fullPage.find(' vs="', away) + 5
		scoreEnd = fullPage.find('"', score)
		awayScore = fullPage[score:scoreEnd]
		if (timeLeft == ""):
			awayScore = 0
		else:
			awayScore = awayScores[awayTeam]

		# see whether they have the quarterly breakdown yet
		if( awayScore == 0 ):
			away1QScore = 0;
			away2QScore = 0;
			away3QScore = 0;
		else:
			away1QScore = int(awayScores1Q[awayTeam]);
			away2QScore = away1QScore + int(awayScores2Q[awayTeam]);
			away3QScore = away2QScore + int(awayScores3Q[awayTeam]);

		# swap for super bowl home team
		if (((int(season) % 2) == 1) and (int(week) == 22)):
			swap = homeTeam
			homeTeam = awayTeam
			awayTeam = swap
			swap = homeScore
			homeScore = awayScore
			awayScore = swap
			swap = home1QScore
			home1QScore = away1QScore
			away1QScore = swap
			swap = home2QScore
			home2QScore = away2QScore
			away2QScore = swap
			swap = home3QScore
			home3QScore = away3QScore
			away3QScore = swap

		# punch it into the database
		cur.execute("select gameID, status, (gameTime - now()) from Game where season=" + str(season) + " and weekNumber=" + str(week) + " and homeTeam='" + homeTeam + "' and awayTeam='" + awayTeam + "'");
		gameID = cur.fetchall()
		
		# this game is already in the database, so just update it
		if len(gameID) > 0:
			# its over, so make it show that
			if timeLeft[:5] == "FINAL":
				cur.execute("update Game set status=" + str(statsUtil.FINAL) + ", timeLeft='', awayScore1Q=" + str(away1QScore) + ", awayScore2Q=" + str(away2QScore) + ", awayScore3Q=" + str(away3QScore) + ", awayScore=" + str(awayScore) + ", homeScore1Q=" + str(home1QScore) + ", homeScore2Q=" + str(home2QScore) + ", homeScore3Q=" + str(home3QScore) + ", homeScore=" + str(homeScore) + ", NFLgameID=" + str(nflID) + " where gameID=" + str(gameID[0][0]))
			# it's in progress, so update the time left
			elif gameID[0][2] <= 0:
				cur.execute("update Game set status=" + str(statsUtil.IN_PROGRESS) + ", timeLeft='" + timeLeft + "', awayScore1Q=" + str(away1QScore) + ", awayScore2Q=" + str(away2QScore) + ", awayScore3Q=" + str(away3QScore) + ", awayScore=" + str(awayScore) + ", homeScore1Q=" + str(home1QScore) + ", homeScore2Q=" + str(home2QScore) + ", homeScore3Q=" + str(home3QScore) + ", homeScore=" + str(homeScore) + ", NFLgameID=" + str(nflID) + " where gameID=" + str(gameID[0][0]))
			# its not over yet, so we need to keep going
			if gameID[0][1] == statsUtil.IN_PROGRESS and timeLeft[:5] != "FINAL":
				allDone = False
		# its not in there yet, so we need to enter it
		else:
			# its over, so just give it a dummy gametime
			if timeLeft[:5] == "FINAL":
				if day[4:6] == "01":
					gameTime = datetime.datetime.strptime((season + 1) + " " + day + " 01:00 PM", "%Y %a, %b %d %I:%M %p")
				else:
					gameTime = datetime.datetime.strptime(season + " " + day + " 01:00 PM", "%Y %a, %b %d %I:%M %p")
				status = statsUtil.FINAL
			# weird bug fix for the one london game with AM PM
			elif timeLeft[-9:] == "AM  PM ET":
				if day[:3] == "Jan":
					gameTime = datetime.datetime.strptime((season + 1) + " " + day + " " + timeLeft[:-7], "%Y %a, %b %d %I:%M %p")
				else:
					gameTime = datetime.datetime.strptime(season + " " + day + " " + timeLeft[:-7], "%Y %a, %b %d %I:%M %p")
				status = statsUtil.FUTURE
			# its in the future (we dont have any in progress logic, because im not 
			#                    waiting until games are happening to load the database)
			else:
				if day[4:6] == "01":
					###gameTime = datetime.datetime.strptime((season + 1) + " " + day + " " + timeLeft[:-4], "%Y %a, %b %d %I:%M %p")
					gameTime = datetime.datetime.strptime(day + " " + ("0" if (len(clock) == 4) else "") + clock + " PM", "%Y%m%d %I:%M %p")
				else:
					###gameTime = datetime.datetime.strptime(season + " " + day + " " + timeLeft[:-4], "%Y %a, %b %d %I:%M %p")
					gameTime = datetime.datetime.strptime(day + " " + ("0" if (len(clock) == 4) else "") + clock + " PM", "%Y%m%d %I:%M %p")
				status = statsUtil.FUTURE
			# add it to the database
			cur.execute("insert into Game (season, weekNumber, homeTeam, homeScore1Q, homeScore2Q, homeScore3Q, homeScore, awayTeam, awayScore1Q, awayScore2Q, awayScore3Q, awayScore, gameTime, lockTime, status, NFLgameID) values (" + season + "," + week + ",'" + homeTeam + "'," + str(home1QScore) + "," + str(home2QScore) + "," + str(home3QScore) + "," + str(homeScore) + ",'" + awayTeam + "'," + str(away1QScore) + "," + str(away2QScore) + "," + str(away3QScore) + "," + str(awayScore) + ",'" + gameTime.strftime("%Y-%m-%d %H:%M:00") + "','" + gameTime.strftime("%Y-%m-%d %H:%M:00") + "'," + str(status) + "," + str(nflID) + ")")

		if (len(gameID) > 0 or status != statsUtil.FUTURE):
			# grab the yardage totals and touchdown counts
			cur.execute("select gameID, concat('NFL_', date_format(gameTime, '%Y%m%d'), '_', if(((season%2) = 1) and (weekNumber=22), homeTeam, awayTeam),'@', if(((season%2) = 1) and (weekNumber=22), awayTeam, homeTeam)) as URL from Game where season=" + str(season) + " and weekNumber=" + str(week) + " and homeTeam='" + homeTeam + "' and awayTeam='" + awayTeam + "'")
			dbInfo = cur.fetchall()[0]
			analyzePage = urllib.urlopen("http://www.cbssports.com/nfl/gametracker/live/" + dbInfo[1].replace("JAX", "JAC").replace("LA", "LAR")).read()
			preHalftime = ((timeLeft == "Halftime") or ((timeLeft[:1] == "Q") and (int(timeLeft[1:2]) < 3)))
			
			# away rushing yardage
			#aStart = analyzePage.find('id="away-netydsrushing"')
			#aStart = analyzePage.find('>', aStart) + 1
			aStart = analyzePage.find('<td>Net Yards Rushing</td>') + 26
			aStart = analyzePage.find('<td>', aStart) + 4
			aEnd = analyzePage.find('<', aStart)
			if ParseInt(analyzePage[aStart:aEnd]):
				awayRushYds = int(analyzePage[aStart:aEnd])
			else:
				awayRushYds = "null"

			# home rushing yardage
			#aStart = analyzePage.find('id="home-netydsrushing"', aEnd)
			#aStart = analyzePage.find('>', aStart) + 1
			aStart = analyzePage.find('<td>', aEnd) + 4
			aEnd = analyzePage.find('<', aStart)
			if ParseInt(analyzePage[aStart:aEnd]):
				homeRushYds = int(analyzePage[aStart:aEnd])
			else:
				homeRushYds = "null"

			# away passing yardage
			#aStart = analyzePage.find('id="away-netydspassing"', aEnd)
			#aStart = analyzePage.find('>', aStart) + 1
			aStart = analyzePage.find('<td>Net Yards Passing</td>') + 26
			aStart = analyzePage.find('<td>', aStart) + 4
			aEnd = analyzePage.find('<', aStart)
			if ParseInt(analyzePage[aStart:aEnd]):
				awayPassYds = int(analyzePage[aStart:aEnd])
			else:
				awayPassYds = "null"

			# home passing yardage
			#aStart = analyzePage.find('id="home-netydspassing"', aEnd)
			#aStart = analyzePage.find('>', aStart) + 1
			aStart = analyzePage.find('<td>', aEnd) + 4
			aEnd = analyzePage.find('<', aStart)
			if ParseInt(analyzePage[aStart:aEnd]):
				homePassYds = int(analyzePage[aStart:aEnd])
			else:
				homePassYds = "null"

			# away TDs
			#aStart = analyzePage.find('id="away-tds"', aEnd)
			#aStart = analyzePage.find('>', aStart) + 1
			aStart = analyzePage.find('<td>Touchdowns</td>', aEnd) + 19
			aStart = analyzePage.find('<td>', aStart) + 4
			aEnd = analyzePage.find('<', aStart)
			if ParseInt(analyzePage[aStart:aEnd]):
				awayTDs = int(analyzePage[aStart:aEnd])
			else:
				awayTDs = "null"

			# home TDs
			#aStart = analyzePage.find('id="home-tds"', aEnd)
			#aStart = analyzePage.find('>', aStart) + 1
			aStart = analyzePage.find('<td>', aEnd) + 4
			aEnd = analyzePage.find('<', aStart)
			if ParseInt(analyzePage[aStart:aEnd]):
				homeTDs = int(analyzePage[aStart:aEnd])
			else:
				homeTDs = "null"

			# swap for super bowl home team
			if ((int(season) % 2) == 1) and (int(week) == 22):
				swap = homeRushYds
				homeRushYds = awayRushYds
				awayRushYds = swap
				swap = homePassYds
				homePassYds = awayPassYds
				awayPassYds = swap
				swap = homeTDs
				homeTDs = awayTDs
				awayTDs = swap

			# enter these numbers into the db
			if awayRushYds != "null" and awayPassYds != "null" and awayTDs != "null" and homeRushYds != "null" and homePassYds != "null" and homeTDs != "null":
				yardQuery = "update Game set awayRushYds=" + str(awayRushYds) + ", awayPassYds=" + str(awayPassYds) + ", awayTDs=" + str(awayTDs) + ", homeRushYds=" + str(homeRushYds) + ", homePassYds=" + str(homePassYds) + ", homeTDs=" + str(homeTDs)
				if preHalftime:
					yardQuery = yardQuery + ", awayRushYds2Q=" + str(awayRushYds) + ", awayPassYds2Q=" + str(awayPassYds) + ", awayTDs2Q=" + str(awayTDs) + ", homeRushYds2Q=" + str(homeRushYds) + ", homePassYds2Q=" + str(homePassYds) + ", homeTDs2Q=" + str(homeTDs)
				yardQuery = yardQuery + " where gameID=" + str(dbInfo[0]);
				cur.execute(yardQuery)

		# next game
		start = next

def ParseInt(s):
	try:
		int(s)
		return True
	except ValueError:
		return False

# see whether this is the main guy or guy
if __name__ == "__main__":
	# see what week we want to grab
	# they gave us arguments, so only grab that week
	if( len(sys.argv) > 2 ):
		season = sys.argv[2]
		week = sys.argv[1]	
	# part of the automation.  check where we are in the database
	else:
		cur.execute("select value from Constants where name='fetchSeason'")
		season = cur.fetchall()[0][0]
		cur.execute("select value from Constants where name='fetchWeek'")
		week = cur.fetchall()[0][0]

	# run the function
	GrabWeekGames(week, season);

	# update the stats
	if( int(week) < 18 ):
		statsUtil.updateStats(week, season)
	else:
		statsUtil.updatePlayoffStats(week, season)
		statsUtil.updateConsolationStats(season)

	# if we've finished up this day, move to the next one
	crontabTime = "* * * * *"
	if allDone:
		cur.execute("select coalesce(min(gameTime), '1982-11-08 12:00:00'), coalesce(min(weekNumber), 29) from Game where status != " + str(statsUtil.FINAL) + " and status != 19")
		gameTime = cur.fetchall()
		cur.execute("update Constants set value='" + str(gameTime[0][1]) + "' where name='fetchWeek'")
		pyTime = datetime.datetime(int(gameTime[0][0][0:4]), int(gameTime[0][0][5:7]), int(gameTime[0][0][8:10]), int(gameTime[0][0][11:13]), int(gameTime[0][0][14:16]), int(gameTime[0][0][17:19]))
		if( (pyTime - datetime.datetime.today()).total_seconds() >= 0 ):
			crontabTime = pyTime.strftime("%M %H %d %m *");

	# update the cronjob
	# get the old versions
	call("crontab -l > /tmp/oldCrontab.txt", shell=True)
	oldCrontab = open("/tmp/oldCrontab.txt", "r")
	newCrontab = open("/tmp/newCrontab.txt", "w")
	
	# scan through to find the needed line
	nextLine = False
	for line in oldCrontab:
		# it's this one, so trim off the old time and insert the new one
		if( nextLine ):
			newCrontab.write( crontabTime + line[line.find("     python /"):] )
			nextLine = False
		# it isnt, so just copy it over
		else:
			newCrontab.write(line)
	
		# check to see if this is the trigger line.  if so, the next one is the guy who needs updated
		if( line[:53] == "# ***AUTO*** task to update the scores in steves pool" ):
			nextLine = True
	
	# close the files
	oldCrontab.close()
	newCrontab.close()
	
	# install the new crontab
	call("crontab /tmp/newCrontab.txt", shell=True)
	
	# mark the latest update
	cur.execute("update Constants set value=now() where name='lastUpdate'")
	
	# clear the cache
	urllib.urlopen("http://localhost/stevePool/helm/flushCache.php").read()

	# commit the changes
	statsUtil.db.commit()
