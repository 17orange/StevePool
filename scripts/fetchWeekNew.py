import urllib.request
import statsUtil
import datetime
import sys
from subprocess import call

# get the util cursor
cur = statsUtil.cur
allDone = True

# function to gather this info
def GrabWeekGames(week, season):
	# grab the live scores from espn
	espnWeek = week
	espnType = "2"
	if( int(week) > 18 ):
		espnWeek = str(int(week) - 18)
		espnType = "3"
	liveScores = str(urllib.request.urlopen("https://www.espn.com/nfl/schedule/_/week/" + espnWeek + "/year/" + season + "/seasontype/" + espnType).read())

	start = liveScores.find("<table style=\"border-collapse:collapse;border-spacing:0\" class=\"Table\">")#"<table class=\"schedule has-team-logos")
	tableEnd = liveScores.find("</table>", start)
	start = liveScores.find("<tr class=", start)
	while( start != -1 ):
		start = liveScores.find("<span class=\"Table__Team away\">", start)
		start = liveScores.find("href=\"/nfl/team/_/name/", start) + 23
		awayEnd = liveScores.find("/", start)
		awayTeam = liveScores[start:awayEnd].upper()
		if( awayTeam == "WSH" ):
			awayTeam = "WAS"
		elif( awayTeam == "LAR" ):
			awayTeam = "LA"

		start = liveScores.find("<span class=\"Table__Team\">", start)
		start = liveScores.find("href=\"/nfl/team/_/name/", start) + 23
		homeEnd = liveScores.find("/", start)
		homeTeam = liveScores[start:homeEnd].upper()
		if( homeTeam == "WSH" ):
			homeTeam = "WAS"
		elif( homeTeam == "LAR" ):
			homeTeam = "LA"
		###print( awayTeam + "@" + homeTeam )

		# skip Damar Hamlin game
		if( homeTeam == "CIN" and awayTeam == "BUF" and week == "17" and season == "2022" ):
			start = -1
			continue

		# get the CBS page to parse this info
		cur.execute("select gameID, concat('NFL_', date_format(gameTime, '%Y%m%d'), '_', if(((season%2) = 1) and (weekNumber=23), homeTeam, awayTeam),'@', if(((season%2) = 1) and (weekNumber=23), awayTeam, homeTeam)) as URL from Game where season=" + str(season) + " and weekNumber=" + str(week) + " and homeTeam='" + homeTeam + "' and awayTeam='" + awayTeam + "'")
		dbInfo = cur.fetchall()[0]
		###print( "http://www.cbssports.com/nfl/gametracker/live/" + dbInfo[1].replace("JAX", "JAC").replace("LAC", "QXV").replace("LA", "LAR").replace("QXV", "LAC"))
		analyzePage = str(urllib.request.urlopen("http://www.cbssports.com/nfl/gametracker/live/" + dbInfo[1].replace("JAX", "JAC").replace("LAC", "QXV").replace("LA", "LAR").replace("QXV", "LAC")).read())

		#start2 = liveScores.find("href=\"/nfl/game?gameId=", start)
		#start = liveScores.find("href=\"/nfl/game/_/gameId/", start) + 25
		#if( start2 != -1 and ((start == 24) or (start2 < start)) ):
		#	start = start2 + 23
		#linkEnd = liveScores.find("\"", start)
		#gameLink = liveScores[start:linkEnd]
		#gameLink = "https://www.espn.com/nfl/boxscore?gameId=" + gameLink
		###pbpLink = "https://www.espn.com/nfl/playbyplay?gameId=" + gameLink[-9:]

		#boxScore = str(urllib.request.urlopen(gameLink).read())
		quarter = 1
		homePass = 0
		awayPass = 0
		homeRush = 0
		awayRush = 0
		homeTDs = 0
		awayTDs = 0
		homeScore = [0, 0, 0, 0]
		awayScore = [0, 0, 0, 0]
		timeLeft = ""

		# game time
		#timeStart = boxScore.find("status-detail\">")
		#timeStart = boxScore.find("class=\"ScoreCell__Time")
		#lineScore = boxScore.find("<table id=\"linescore\"")
		timeStart = analyzePage.find("<div class=\"time\"")
		lineScore = analyzePage.find("<table class=\"linescore\">")
		####lineScore = boxScore.find("class=\"ResponsiveTable Gamestrip__Table\"")
		if( timeStart != -1 and lineScore != -1 ):
			pregameStatus = analyzePage.find("SCHEDULED-status")
			inProgressStatus = analyzePage.find("INPROGRESS-status")
			finalStatus = analyzePage.find("FINAL-status")
			if( finalStatus != -1 ):
				timeLeft = "FINAL"
			elif( inProgressStatus != -1 ):
				quarter = analyzePage.find("<div class=\"quarter")
				quarter = analyzePage.find(">", quarter)
				timeEnd = analyzePage.find("</div>", timeStart)
				timeLeft = "Q" + analyzePage[(quarter+1):(quarter+2)] + " " + analyzePage[(timeStart + 18):timeEnd]
				if( timeLeft == "QE 2nd" ):
					timeLeft = "Halftime"
				elif( timeLeft == "QE 4th" ):
					timeLeft = "End Reg"
				elif( timeLeft[:2] == "QO" ):
					timeLeft = "OT" + timeLeft[2:]
			else:
				timeLeft = ""

			#timeEnd = boxScore.find("</span>", timeStart)
			#timeLeft = boxScore[(timeStart + 15):(timeEnd+6)]
			#print("##" + timeLeft + "##")
			#brk = timeLeft.find(" - ")
			#if timeLeft[1:8].lower() == "alftime":
			#	timeLeft = "Halftime"
			#elif timeLeft[1:5].lower() == "inal":
			#	timeLeft = "FINAL"
			#elif brk != -1:
			#	timeLeft = "Q" + timeLeft[(brk + 3)] + " " + timeLeft[:brk]
			#else:
			#	timeLeft = ""

			if( pregameStatus == -1 ):
				# score by quarters
				lineScore = analyzePage.find("<td class=\"score\">", lineScore) + 18
				scoreEnd = analyzePage.find("</td>", lineScore)
				if( (lineScore < scoreEnd) and analyzePage[lineScore:scoreEnd].isdigit() ):
					awayScore[0] = int(analyzePage[lineScore:scoreEnd])
					awayScore[1] = int(analyzePage[lineScore:scoreEnd])
					awayScore[2] = int(analyzePage[lineScore:scoreEnd])
				lineScore = analyzePage.find("<td class=\"score\">", lineScore) + 18
				scoreEnd = analyzePage.find("</td>", lineScore)
				if( (lineScore < scoreEnd) and analyzePage[lineScore:scoreEnd].isdigit() ):
					awayScore[1] += int(analyzePage[lineScore:scoreEnd])
					awayScore[2] += int(analyzePage[lineScore:scoreEnd])
				lineScore = analyzePage.find("<td class=\"score\">", lineScore) + 18
				scoreEnd = analyzePage.find("</td>", lineScore)
				if( (lineScore < scoreEnd) and analyzePage[lineScore:scoreEnd].isdigit() ):
					awayScore[2] += int(analyzePage[lineScore:scoreEnd])
				lineScore = analyzePage.find("<td class=\"total-score\">", lineScore) + 24
				scoreEnd = analyzePage.find("</td>", lineScore)
				awayScore[3] = int(analyzePage[lineScore:scoreEnd])
				lineScore = analyzePage.find("<td class=\"score\">", lineScore) + 18
				scoreEnd = analyzePage.find("</td>", lineScore)
				if( (lineScore < scoreEnd) and analyzePage[lineScore:scoreEnd].isdigit() ):
					homeScore[0] = int(analyzePage[lineScore:scoreEnd])
					homeScore[1] = int(analyzePage[lineScore:scoreEnd])
					homeScore[2] = int(analyzePage[lineScore:scoreEnd])
				lineScore = analyzePage.find("<td class=\"score\">", lineScore) + 18
				scoreEnd = analyzePage.find("</td>", lineScore)
				if( (lineScore < scoreEnd) and analyzePage[lineScore:scoreEnd].isdigit() ):
					homeScore[1] += int(analyzePage[lineScore:scoreEnd])
					homeScore[2] += int(analyzePage[lineScore:scoreEnd])
				lineScore = analyzePage.find("<td class=\"score\">", lineScore) + 18
				scoreEnd = analyzePage.find("</td>", lineScore)
				if( (lineScore < scoreEnd) and analyzePage[lineScore:scoreEnd].isdigit() ):
					homeScore[2] += int(analyzePage[lineScore:scoreEnd])
				lineScore = analyzePage.find("<td class=\"total-score\">", lineScore) + 24
				scoreEnd = analyzePage.find("</td>", lineScore)
				homeScore[3] = int(analyzePage[lineScore:scoreEnd])

		# swap for super bowl home team
		if (((int(season) % 2) == 1) and (int(week) == 23)):
			swap = homeTeam
			homeTeam = awayTeam
			awayTeam = swap
			swap = homeScore
			homeScore = awayScore
			awayScore = swap

		# punch it into the database
		cur.execute("select gameID, status, (gameTime - now()) from Game where season=" + season + " and weekNumber=" + week + " and homeTeam='" + homeTeam + "' and awayTeam='" + awayTeam + "'");
		gameID = cur.fetchall()

		# this game is already in the database, so just update it
		if len(gameID) > 0:
			# its over, so make it show that
			if timeLeft[:5] == "FINAL":
				cur.execute("update Game set status=" + str(statsUtil.FINAL) + ", timeLeft='', awayScore1Q=" + str(awayScore[0]) + ", awayScore2Q=" + str(awayScore[1]) + ", awayScore3Q=" + str(awayScore[2]) + ", awayScore=" + str(awayScore[3]) + ", homeScore1Q=" + str(homeScore[0]) + ", homeScore2Q=" + str(homeScore[1]) + ", homeScore3Q=" + str(homeScore[2]) + ", homeScore=" + str(homeScore[3]) + " where gameID=" + str(gameID[0][0]))
			# it's in progress, so update the time left
			elif gameID[0][2] <= 0:
				cur.execute("update Game set status=" + str(statsUtil.IN_PROGRESS) + ", timeLeft='" + timeLeft + "', awayScore1Q=" + str(awayScore[0]) + ", awayScore2Q=" + str(awayScore[1]) + ", awayScore3Q=" + str(awayScore[2]) + ", awayScore=" + str(awayScore[3]) + ", homeScore1Q=" + str(homeScore[0]) + ", homeScore2Q=" + str(homeScore[1]) + ", homeScore3Q=" + str(homeScore[2]) + ", homeScore=" + str(homeScore[3]) + " where gameID=" + str(gameID[0][0]))
			# its not over yet, so we need to keep going
			if gameID[0][1] == statsUtil.IN_PROGRESS and timeLeft[:5] != "FINAL":
				allDone = False
		# its not in there yet, so we need to enter it
		#else:
			#datadate = boxScore.find(" data-date=\"") + 12
			#datadate = boxScore[datadate:(datadate + 17)]
			#gameTime = datetime.datetime.strptime(datadate, "%Y-%m-%dT%H:%MZ")
			# its over, so just give it a dummy gametime
			#if timeLeft[:5] == "FINAL":
#				if day[4:6] == "01":
#					gameTime = datetime.datetime.strptime((season + 1) + " " + day + " 01:00 PM", "%Y %a, %b %d %I:%M %p")
#				else:
#					gameTime = datetime.datetime.strptime(season + " " + day + " 01:00 PM", "%Y %a, %b %d %I:%M %p")
				#status = statsUtil.FINAL
#			# weird bug fix for the one london game with AM PM
#			elif timeLeft[-9:] == "AM  PM ET":
#				if day[:3] == "Jan":
#					gameTime = datetime.datetime.strptime((season + 1) + " " + day + " " + timeLeft[:-7], "%Y %a, %b %d %I:%M %p")
#				else:
#					gameTime = datetime.datetime.strptime(season + " " + day + " " + timeLeft[:-7], "%Y %a, %b %d %I:%M %p")
#				status = statsUtil.FUTURE
			# its in the future (we dont have any in progress logic, because im not 
			#                    waiting until games are happening to load the database)
			#else:
#				if day[4:6] == "01":
#					###gameTime = datetime.datetime.strptime((season + 1) + " " + day + " " + timeLeft[:-4], "%Y %a, %b %d %I:%M %p")
#					gameTime = datetime.datetime.strptime(day + " " + ("0" if (len(clock) == 4) else "") + clock + " PM", "%Y%m%d %I:%M %p")
#				else:
#					###gameTime = datetime.datetime.strptime(season + " " + day + " " + timeLeft[:-4], "%Y %a, %b %d %I:%M %p")
#					gameTime = datetime.datetime.strptime(day + " " + ("0" if (len(clock) == 4) else "") + clock + " PM", "%Y%m%d %I:%M %p")
				#status = statsUtil.FUTURE
			# add it to the database (THESE ARE IN ZULU TIME!! YOU NEED TO CORRECT THEM BY HAND FOR EST/EDT)
			#cur.execute("insert into Game (season, weekNumber, homeTeam, homeScore1Q, homeScore2Q, homeScore3Q, homeScore, awayTeam, awayScore1Q, awayScore2Q, awayScore3Q, awayScore, gameTime, lockTime, status, NFLgameID) values (" + season + "," + week + ",'" + homeTeam + "'," + str(homeScore[0]) + "," + str(homeScore[1]) + "," + str(homeScore[2]) + "," + str(homeScore[3]) + ",'" + awayTeam + "'," + str(awayScore[0]) + "," + str(awayScore[1]) + "," + str(awayScore[2]) + "," + str(awayScore[3]) + ",'" + gameTime.strftime("%Y-%m-%d %H:%M:00") + "','" + gameTime.strftime("%Y-%m-%d %H:%M:00") + "'," + str(status) + ",0)")
		if (len(gameID) > 0): # or status != statsUtil.FUTURE):
			# grab the yardage totals and touchdown counts
			preHalftime = ((timeLeft == "Halftime") or ((timeLeft[:1] == "Q") and timeLeft[1:2].isnumeric() and (int(timeLeft[1:2]) < 3)))
			
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
			if ((int(season) % 2) == 1) and (int(week) == 23):
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

#		if gameID[0][1] != statsUtil.FUTURE:
#			preHalftime = ((timeLeft == "Halftime") or ((timeLeft[:1] == "Q") and (int(timeLeft[1:2]) < 3)))
#			yardQuery = "update Game set awayRushYds=" + str(awayRush) + ", awayPassYds=" + str(awayPass) + ", awayTDs=" + str(awayTDs) + ", homeRushYds=" + str(homeRush) + ", homePassYds=" + str(homePass) + ", homeTDs=" + str(homeTDs)
#			if preHalftime:
#				yardQuery = yardQuery + ", awayRushYds2Q=" + str(awayRush) + ", awayPassYds2Q=" + str(awayPass) + ", awayTDs2Q=" + str(awayTDs) + ", homeRushYds2Q=" + str(homeRush) + ", homePassYds2Q=" + str(homePass) + ", homeTDs2Q=" + str(homeTDs)
#			yardQuery = yardQuery + " where gameID=" + str(gameID[0][0]);
#			cur.execute(yardQuery)

		nextRow = liveScores.find("<span class=\"Table__Team away\">", start)
		if( nextRow > tableEnd ):
			start = liveScores.find("<table style=\"border-collapse:collapse;border-spacing:0\" class=\"Table\">", tableEnd)
			tableEnd = liveScores.find("</table>", start)
			byeWeek = liveScores.find("byeweek", start)
			start = liveScores.find("<tr class=", start)
			# skip bye weeks
			if( byeWeek != -1 and byeWeek < tableEnd ):
				start = -1
		else:
			start = nextRow

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
	if( int(week) < 19 ):
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
			newCrontab.write( crontabTime + line[line.find("     python3 /"):] )
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
	urllib.request.urlopen("https://bradplusplus.com/stevePool/helm/flushCache.php").read()

	# commit the changes
	statsUtil.db.commit()
