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
			elif liveScores[start:(start+4)] != "null" and liveScores[(start+2):(start+6)] != "inal" and liveScores[(start+1):(start+8)] != "Pregame":
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
	pageUrl = "http://www.nfl.com/scores/" + season + "/"
	if( int(week) < 18 ):
		pageUrl = pageUrl + "REG"
	else:
		pageUrl = pageUrl + "POST"
	pageUrl = pageUrl + week
	fullPage = urllib.urlopen(pageUrl).read()

	# get the start position of the first game
	start = fullPage.find('<div class="new-score-box-heading">')
	allDone = True
	while( start != -1 ):
		# see where the next game starts
		next = fullPage.find('<div class="new-score-box-heading">', start + 1)

		# get the game date
		day = fullPage.find('<span class="date" title="Date Airing">', start) + 39
		dayEnd = fullPage.find('<', day)
		day = fullPage[day:dayEnd]

		# get the away team
		away = fullPage.find('<div class="away-team">', start)
		away = fullPage.find('href="/teams/profile?team=', away) + 26
		awayEnd = fullPage.find('"', away)
		awayTeam = fullPage[away:awayEnd]

		# get the away score
		score = fullPage.find('class="total-score">', away) + 20
		scoreEnd = fullPage.find('<', score)
		awayScore = fullPage[score:scoreEnd]
		if (awayScore == "--"):
			awayScore = 0
		elif (awayScore == "#{away.score.T}" ):
			awayScore = awayScores[awayTeam]
		else:
			awayScore = int(awayScore)

		# see whether they have the quarterly breakdown yet
		away1QScore = 0;
		away2QScore = 0;
		away3QScore = 0;
		firstQ = fullPage.find('<span class="first-qt">', score)
		if( firstQ != -1 and (firstQ < next or next == -1) ):
			firstQEnd = fullPage.find('<', firstQ + 23)
			away1QScore = fullPage[(firstQ + 23):firstQEnd]
			if (away1QScore == "--"):
				away1QScore = 0;
			elif (away1QScore == "#{away.score.1}"):
				away1QScore = int(awayScores1Q[awayTeam]);
			else:
				away1QScore = int(away1QScore)
		secondQ = fullPage.find('<span class="second-qt">', score)
		if( secondQ != -1 and (secondQ < next or next == -1) ):
			secondQEnd = fullPage.find('<', secondQ + 24)
			away2QScore = fullPage[(secondQ + 24):secondQEnd]
			if (away2QScore == "--"):
				away2QScore = 0;
			elif (away2QScore == "#{away.score.2}"):
				away2QScore = int(awayScores2Q[awayTeam]);
			else:
				away2QScore = int(away2QScore)
		away2QScore = away2QScore + away1QScore
		thirdQ = fullPage.find('<span class="third-qt">', score)
		if( thirdQ != -1 and (thirdQ < next or next == -1) ):
			thirdQEnd = fullPage.find('<', thirdQ + 23)
			away3QScore = fullPage[(thirdQ + 23):thirdQEnd]
			if (away3QScore == "--"):
				away3QScore = 0;
			elif (away3QScore == "#{away.score.3}"):
				away3QScore = int(awayScores3Q[awayTeam]);
			else:
				away3QScore = int(away3QScore)
		away3QScore = away3QScore + away2QScore

		# get the home team
		home = fullPage.find('<div class="home-team">', start)
		home = fullPage.find('href="/teams/profile?team=', home) + 26
	 	homeEnd = fullPage.find('"', home)
		homeTeam = fullPage[home:homeEnd]

		# get the home score
		score = fullPage.find('class="total-score">', home) + 20
		scoreEnd = fullPage.find('<', score)
		homeScore = fullPage[score:scoreEnd]
		if (homeScore == "--"):
			homeScore = 0
		elif (homeScore == "#{home.score.T}" ):
			homeScore = homeScores[homeTeam]
		else:
			homeScore = int(homeScore)

		# see whether they have the quarterly breakdown yet
		home1QScore = 0;
		home2QScore = 0;
		home3QScore = 0;
		firstQ = fullPage.find('<span class="first-qt">', score)
		if( firstQ != -1 and (firstQ < next or next == -1) ):
			firstQEnd = fullPage.find('<', firstQ + 23)
			home1QScore = fullPage[(firstQ + 23):firstQEnd]
			if (home1QScore == "--"):
				home1QScore = 0;
			elif (home1QScore == "#{home.score.1}"):
				home1QScore = int(homeScores1Q[homeTeam]);
			else:
				home1QScore = int(home1QScore)
		secondQ = fullPage.find('<span class="second-qt">', score)
		if( secondQ != -1 and (secondQ < next or next == -1) ):
			secondQEnd = fullPage.find('<', secondQ + 24)
			home2QScore = fullPage[(secondQ + 24):secondQEnd]
			if (home2QScore == "--"):
				home2QScore = 0;
			elif (home2QScore == "#{home.score.2}"):
				home2QScore = int(homeScores2Q[homeTeam]);
			else:
				home2QScore = int(home2QScore)
		home2QScore = home2QScore + home1QScore
		thirdQ = fullPage.find('<span class="third-qt">', score)
		if( thirdQ != -1 and (thirdQ < next or next == -1) ):
			thirdQEnd = fullPage.find('<', thirdQ + 23)
			home3QScore = fullPage[(thirdQ + 23):thirdQEnd]
			if (home3QScore == "--"):
				home3QScore = 0;
			elif (home3QScore == "#{home.score.3}"):
				home3QScore = int(homeScores3Q[homeTeam]);
			else:
				home3QScore = int(home3QScore)
		home3QScore = home3QScore + home2QScore

		# get the time left
		time = fullPage.find('<span class="time-left"', home)
		time = fullPage.find('>', time) + 1
		timeEnd = fullPage.find('</span>', time)
		timeLeft = fullPage[time:timeEnd]
		if( timeLeft[0:38] == '<span class="down-yardline">#{posdisp}' ):
			timeLeft = homeTimes[homeTeam]

		# get the nfl id
		share = fullPage.find('<div id="share-', time) + 23
		nflID = int(fullPage[share:(share+2)])

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
				gameTime = datetime.datetime.strptime(season + " " + day + " 01:00 PM", "%Y %a, %b %d %I:%M %p")
				status = statsUtil.FINAL
			# weird bug fix for the one london game with AM PM
			elif timeLeft[-9:] == "AM  PM ET":
				gameTime = datetime.datetime.strptime(season + " " + day + " " + timeLeft[:-7], "%Y %a, %b %d %I:%M %p")
				status = statsUtil.FUTURE
			# its in the future (we dont have any in progress logic, because im not 
			#                    waiting until games are happening to load the database)
			else:
				gameTime = datetime.datetime.strptime(season + " " + day + " " + timeLeft[:-4], "%Y %a, %b %d %I:%M %p")
				status = statsUtil.FUTURE
			# add it to the database
			cur.execute("insert into Game (season, weekNumber, homeTeam, homeScore1Q, homeScore2Q, homeScore3Q, homeScore, awayTeam, awayScore1Q, awayScore2Q, awayScore3Q, awayScore, gameTime, lockTime, status, NFLgameID) values (" + season + "," + week + ",'" + homeTeam + "'," + str(home1QScore) + "," + str(home2QScore) + "," + str(home3QScore) + "," + str(homeScore) + ",'" + awayTeam + "'," + str(away1QScore) + "," + str(away2QScore) + "," + str(away3QScore) + "," + str(awayScore) + ",'" + gameTime.strftime("%Y-%m-%d %H:%M:00") + "','" + gameTime.strftime("%Y-%m-%d %H:%M:00") + "'," + str(status) + "," + str(nflID) + ")")

		if len(gameID) > 0 or status != statsUtil.FUTURE:
			# grab the yardage totals and touchdown counts
			cur.execute("select gameID, concat('NFL_', date_format(gameTime, '%Y%m%d'), '_', awayTeam,'@', homeTeam) as URL from Game where season=" + str(season) + " and weekNumber=" + str(week) + " and homeTeam='" + homeTeam + "' and awayTeam='" + awayTeam + "'")
			dbInfo = cur.fetchall()[0]
			analyzePage = urllib.urlopen("http://www.cbssports.com/nfl/gametracker/live/" + dbInfo[1]).read()
			preHalftime = ((timeLeft == "Halftime") or ((timeLeft[:1] == "Q") and (int(timeLeft[1:2]) < 3)))

			# away rushing yardage
			aStart = analyzePage.find('id="away-netydsrushing"')
			aStart = analyzePage.find('>', aStart) + 1
			aEnd = analyzePage.find('<', aStart)
			awayRushYds = int(analyzePage[aStart:aEnd])

			# home rushing yardage
			aStart = analyzePage.find('id="home-netydsrushing"', aEnd)
			aStart = analyzePage.find('>', aStart) + 1
			aEnd = analyzePage.find('<', aStart)
			homeRushYds = int(analyzePage[aStart:aEnd])

			# away passing yardage
			aStart = analyzePage.find('id="away-netydspassing"', aEnd)
			aStart = analyzePage.find('>', aStart) + 1
			aEnd = analyzePage.find('<', aStart)
			awayPassYds = int(analyzePage[aStart:aEnd])

			# home passing yardage
			aStart = analyzePage.find('id="home-netydspassing"', aEnd)
			aStart = analyzePage.find('>', aStart) + 1
			aEnd = analyzePage.find('<', aStart)
			homePassYds = int(analyzePage[aStart:aEnd])

			# away TDs
			aStart = analyzePage.find('id="away-tds"', aEnd)
			aStart = analyzePage.find('>', aStart) + 1
			aEnd = analyzePage.find('<', aStart)
			awayTDs = int(analyzePage[aStart:aEnd])

			# home TDs
			aStart = analyzePage.find('id="home-tds"', aEnd)
			aStart = analyzePage.find('>', aStart) + 1
			aEnd = analyzePage.find('<', aStart)
			homeTDs = int(analyzePage[aStart:aEnd])

			# enter these numbers into the db
			#print str(dbInfo[0]) + " => " + str(preHalftime)
			yardQuery = "update Game set awayRushYds=" + str(awayRushYds) + ", awayPassYds=" + str(awayPassYds) + ", awayTDs=" + str(awayTDs) + ", homeRushYds=" + str(homeRushYds) + ", homePassYds=" + str(homePassYds) + ", homeTDs=" + str(homeTDs)
			if preHalftime:
				yardQuery = yardQuery + ", awayRushYds2Q=" + str(awayRushYds) + ", awayPassYds2Q=" + str(awayPassYds) + ", awayTDs2Q=" + str(awayTDs) + ", homeRushYds2Q=" + str(homeRushYds) + ", homePassYds2Q=" + str(homePassYds) + ", homeTDs2Q=" + str(homeTDs)
			yardQuery = yardQuery + " where gameID=" + str(dbInfo[0]);
			cur.execute(yardQuery)

		# next game
		start = next

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
		cur.execute("select coalesce(min(gameTime), '1982-11-08 12:00:00'), coalesce(min(weekNumber), 29) from Game where status != " + str(statsUtil.FINAL))
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
	
	# commit the changes
	statsUtil.db.commit()
