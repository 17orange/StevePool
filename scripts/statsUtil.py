import MySQLdb

UNKNOWN = 0
FUTURE = 1
IN_PROGRESS = 2
FINAL = 3

# get a connection to the database
db = MySQLdb.connect(host="localhost", user="root", passwd="mudgy17budger", db="StevePool")
cur = db.cursor()

# define the update function
def updateStats(week, season):
	# reset everyone's stats for this week
	cur.execute("update WeekResult inner join (select userID, weekNumber, season, sum(if((winner=homeTeam and homeScore>awayScore) or (winner=awayTeam and awayScore>homeScore), Pick.points, 0)) as points from Game join Pick using (gameID) where weekNumber=" + week + " and season=" + season + " group by userID) as PS using (userID, weekNumber, season) set WeekResult.points=PS.points")

	# assign this week's winner if it's over
	cur.execute("select sum(status<" + str(FINAL) + ") from Game where season=" + season + " and weekNumber=" + week)
	num = cur.fetchall()
	if num[0][0] == 0:
		# grab the MNF score for tiebreaking use
		cur.execute("select homeScore + awayScore from Game where weekNumber=" + week + " and season=" + season + " order by tieBreakOrder desc, gameTime desc, gameID desc limit 1")
		MNF = cur.fetchall()

		# grab the top score for this week
		cur.execute("select userID, abs(tieBreaker - " + str(MNF[0][0]) + "), tieBreaker from WeekResult as wr1 where season=" + season + " and weekNumber=" + week + " and points=(select max(points) from WeekResult where season=wr1.season and weekNumber=wr1.weekNumber)")
		winners = cur.fetchall()

		# only one, so bump up his weekly wins
		if len(winners) == 1:
			cur.execute("update SeasonResult set weeklyWins=weeklyWins + 1 where userID=" + str(winners[0][0]) + " and season=" + season)
		# more than one, so check the tiebreakers
		else:
			# cycle through to find the best of the two
			minTB1 = 10000
			minTB2 = 10000
			for user in winners:
				if user[1] < minTB1:
					minTB1 = user[1]
					minTB2 = user[2]
				elif user[1] == minTB1 and user[2] < minTB2:
					minTB2 = user[2]
			
			# assign the wins to the best people for it
			for user in winners:
				if user[1] == minTB1 and user[2] == minTB2:
					cur.execute("update SeasonResult set weeklyWins=weeklyWins + 1 where userID=" + str(user[0]) + " and season=" + season)

		# as of 2019 season, we're no longer awarding average score for your first miss
		# find the average score
		#cur.execute("select round(sum(points)/(select count(distinct(userID)) from Pick join Game using (gameID) where season=WeekResult.season and weekNumber=WeekResult.weekNumber and winner is not null)) - 5 from WeekResult where weekNumber=" + week + " and season=" + season )
		#avgScore = cur.fetchall()

		# now see if anyone missed the week
		cur.execute("select userID from WeekResult where season=" + season + " and weekNumber=" + week + " and userID not in (select distinct(userID) from Pick join Game using (gameID) where weekNumber=WeekResult.weekNumber and season=WeekResult.season and winner is not null)")
		noPicks = cur.fetchall()
		for user in noPicks:
			# if this guy has never missed before, give him that score
			#cur.execute("update WeekResult join SeasonResult using (userID, season) set WeekResult.points=" + str(avgScore[0][0]) + " where userID=" + str(user[0]) + " and missedWeeks=0 and season=" + season + " and weekNumber=" + week)

			# now bump his missedWeeks
			cur.execute("update SeasonResult set missedWeeks=missedWeeks+1 where userID=" + str(user[0]) + " and season=" + season)

	# give everyone their correct picks total
	cur.execute("update SeasonResult set correctPicks=(select sum(if((winner=homeTeam && homeScore>awayScore), 1, 0) || if((winner=awayTeam && awayScore > homeScore), 1,0)) from Pick join Game using (gameID) where season=SeasonResult.season and userID=SeasonResult.userID) where season=" + season)

	# reset everyone's season stats
	cur.execute("update SeasonResult inner join (select userID, season, sum(points) as points from WeekResult where season=" + season + " group by userID) as PS using (userID, season) set SeasonResult.points=PS.points")

	# update division winners
	cur.execute("update SeasonResult set inPlayoffs='N', firstRoundBye='N' where season=" + season)
	cur.execute("select distinct(divID) from SeasonResult where season=" + season )
	divisions = cur.fetchall()
	for div in divisions:
		# add in the division leaders
		winnerCount = 0
		winnerIDs = []
		currRow = [0,50000,0,0,0]
		cur.execute("select userID, points, weeklyWins, missedWeeks, correctPicks from SeasonResult where season=" + season + " and divID=" + str(div[0]) + " order by points desc, weeklyWins desc, missedWeeks, correctPicks desc, userID")
		leaders = cur.fetchall()
		for player in leaders:
			# see if this row is ahead of the current one
			if winnerCount < 5 and (player[1] < currRow[1] or (player[1] == currRow[1] and (player[2] < currRow[2] or (player[2] == currRow[2] and ((currRow[3] == 0 and player[3] > 0) or player[4] < currRow[4]))))):
				winnerCount = winnerCount + len(winnerIDs)
				for winner in winnerIDs:
					cur.execute("update SeasonResult set inPlayoffs=if(" + str(winnerCount) + " <= 5, 'Y', 'R') where season=" + season + " and userID=" + str(winner))
				winnerIDs = [player[0]]
				currRow = player
			else:
				winnerIDs.append( player[0] )

		# see if we ran out of people (should only be at the beginning of the season)
		if winnerCount < 5:
			for winner in winnerIDs:
				cur.execute("update SeasonResult set inPlayoffs='R' where season=" + season + " and userID=" + str(winner))

	# set the byes, then add in the wild cards
	cur.execute("select distinct(confID) from SeasonResult join Division using(divID) where season=" + season )
	conferences = cur.fetchall()
	for conf in conferences:
		# grab the top 5 division winners
		winnerCount = 0
		winnerIDs = []
		currRow = [0,50000,0,0,0]
		cur.execute("select userID, points, weeklyWins, missedWeeks, correctPicks from SeasonResult join Division using (divID) where season=" + season + " and confID=" + str(conf[0]) + " and inPlayoffs in ('Y', 'R') order by points desc, weeklyWins desc, missedWeeks, correctPicks desc, userID")
		leaders = cur.fetchall()
		for player in leaders:
			# see if this row is ahead of the current one
			if winnerCount < 5 and (player[1] < currRow[1] or (player[1] == currRow[1] and (player[2] < currRow[2] or (player[2] == currRow[2] and ((currRow[3] == 0 and player[3] > 0) or player[4] < currRow[4]))))):
				winnerCount = winnerCount + len(winnerIDs)
				for winner in winnerIDs:
					cur.execute("update SeasonResult set firstRoundBye=if(" + str(winnerCount) + " <= 5, 'Y', 'R') where season=" + season + " and userID=" + str(winner))
				winnerIDs = [player[0]]
				currRow = player
			else:
				winnerIDs.append( player[0] )
		
		# see if we ran out of people (should only be at the beginning of the season)
		if winnerCount < 5:
			for winner in winnerIDs:
				cur.execute("update SeasonResult set firstRoundBye='R' where season=" + season + " and userID=" + str(winner))

		# now grab the wild cards who HAVENT qualified yet
		cur.execute("select count(*) from SeasonResult join Division using (divID) where season=" + season + " and confID=" + str(conf[0]) + " and inPlayoffs='Y' " )
		#cur.execute("select count(*) from SeasonResult join Division using (divID) where season=" + season + " and confID=" + str(conf[0]) + " and inPlayoffs in ('Y', 'R')" )
		winnerCount = cur.fetchall()
		winnerCount = winnerCount[0][0]
		winnerIDs = []
		currRow = [0,50000,0,0,0]
		cur.execute("select userID, points, weeklyWins, missedWeeks, correctPicks from SeasonResult join Division using (divID) where season=" + season + " and confID=" + str(conf[0]) + " and inPlayoffs in ('N', 'R') order by points desc, weeklyWins desc, missedWeeks, correctPicks desc, userID")
		#cur.execute("select userID, points, weeklyWins, missedWeeks, correctPicks from SeasonResult join Division using (divID) where season=" + season + " and confID=" + str(conf[0]) + " and inPlayoffs='N' order by points desc, weeklyWins desc, missedWeeks, correctPicks desc, userID")
		leaders = cur.fetchall()
		for player in leaders:
			# see if this row is ahead of the current one
			if winnerCount < 37 and (player[1] < currRow[1] or (player[1] == currRow[1] and (player[2] < currRow[2] or (player[2] == currRow[2] and ((currRow[3] == 0 and player[3] > 0) or player[4] < currRow[4]))))):
				winnerCount = winnerCount + len(winnerIDs)
				for winner in winnerIDs:
					cur.execute("update SeasonResult set inPlayoffs=if(" + str(winnerCount) + " <= 37, 'Y', 'R') where season=" + season + " and userID=" + str(winner))
				winnerIDs = [player[0]]
				currRow = player
			else:
				winnerIDs.append( player[0] )

		# see if we ran out of people (should only be at the beginning of the season)
		if winnerCount < 37:
			for winner in winnerIDs:
				cur.execute("update SeasonResult set inPlayoffs='R' where season=" + season + " and userID=" + str(winner))

	# see if the regular season is final
	cur.execute("select sum(status<" + str(FINAL) + ") from Game where season=" + season + " and weekNumber=" + week)
	num = cur.fetchall()
	if int(week) == 18 and num[0][0] == 0:
		# see how many of these there are
		cur.execute("select count(*) from SeasonResult where season=" + season + " and firstRoundBye='Y'")
		byeCount = cur.fetchall()[0][0]
		cur.execute("select count(*) from SeasonResult where season=" + season + " and inPlayoffs='Y'")
		playoffCount = cur.fetchall()[0][0]

		# add in the needed results
		cur.execute("insert into PlayoffResult (userID, season, weekNumber, advances) select userID, season, 19, if(firstRoundBye='Y', 'Y', 'R') from SeasonResult where season=" + str(season) + " and inPlayoffs='Y'")
		cur.execute("insert into PlayoffResult (userID, season, weekNumber, advances) select userID, season, 20, 'R' from SeasonResult where season=" + str(season) + " and inPlayoffs='Y'")
		cur.execute("insert into PlayoffResult (userID, season, weekNumber, advances) select userID, season, 21, 'R' from SeasonResult where season=" + str(season) + " and inPlayoffs='Y'")
		cur.execute("insert into PlayoffResult (userID, season, weekNumber, advances) select userID, season, 23, 'R' from SeasonResult where season=" + str(season) + " and inPlayoffs='Y'")
		cur.execute("insert into Pick (userID, gameID, points) select userID, gameID, 0 from PlayoffResult join Game using (weekNumber, season) where weekNumber=19 and season=" + str(season) + " order by gameTime desc")
                #cur.execute("update Pick set points=2 where gameID=(select gameID from Game where weekNumber=18 and season=" + str(season) + " order by gameTime desc limit 1,1)")
                #cur.execute("update Pick set points=3 where gameID=(select gameID from Game where weekNumber=18 and season=" + str(season) + " order by gameTime desc limit 2,1)")
                #cur.execute("update Pick set points=4 where gameID=(select gameID from Game where weekNumber=18 and season=" + str(season) + " order by gameTime desc limit 3,1)")
		cur.execute("insert into ConsolationResult (userID, season) select userID, season from SeasonResult where season=" + str(season) + " and inPlayoffs='N'")


# define the update function
def updatePlayoffStats(week, season):
	# reset playoff pool stats for this week
	cur.execute("update PlayoffResult inner join (select userID, weekNumber, season, sum(if(status=1 or status=19, 0, if(type='winner', if((winner=homeTeam and homeScore>awayScore) or (winner=awayTeam and awayScore>homeScore), Pick.points, 0),if(type='winner3Q', if((winner=homeTeam and homeScore3Q>awayScore3Q) or (winner=awayTeam and awayScore3Q>homeScore3Q) or (winner='TIE' and homeScore3Q=awayScore3Q), Pick.points, 0),if(type='winner2Q', if((winner=homeTeam and homeScore2Q>awayScore2Q) or (winner=awayTeam and awayScore2Q>homeScore2Q) or (winner='TIE' and homeScore2Q=awayScore2Q), Pick.points, 0),if(type='winner1Q', if((winner=homeTeam and homeScore1Q>awayScore1Q) or (winner=awayTeam and awayScore1Q>homeScore1Q) or (winner='TIE' and homeScore1Q=awayScore1Q), Pick.points, 0),if(type='passYds', if((winner=homeTeam and homePassYds>awayPassYds) or (winner=awayTeam and awayPassYds>homePassYds) or (winner='TIE' and homePassYds=awayPassYds), Pick.points, 0),if(type='passYds2Q', if((winner=homeTeam and homePassYds2Q>awayPassYds2Q) or (winner=awayTeam and awayPassYds2Q>homePassYds2Q) or (winner='TIE' and homePassYds2Q=awayPassYds2Q), Pick.points, 0),if(type='rushYds', if((winner=homeTeam and homeRushYds>awayRushYds) or (winner=awayTeam and awayRushYds>homeRushYds) or (winner='TIE' and homeRushYds=awayRushYds), Pick.points, 0),if(type='rushYds2Q', if((winner=homeTeam and homeRushYds2Q>awayRushYds2Q) or (winner=awayTeam and awayRushYds2Q>homeRushYds2Q) or (winner='TIE' and homeRushYds2Q=awayRushYds2Q), Pick.points, 0),if(type='TDs', if((winner=homeTeam and homeTDs>awayTDs) or (winner=awayTeam and awayTDs>homeTDs) or (winner='TIE' and homeTDs=awayTDs), Pick.points, 0),if(type='TDs2Q', if((winner=homeTeam and homeTDs2Q>awayTDs2Q) or (winner=awayTeam and awayTDs2Q>homeTDs2Q) or (winner='TIE' and homeTDs2Q=awayTDs2Q), Pick.points, 0),0)))))))))))) as points from Game join Pick using (gameID) where weekNumber=" + week + " and season=" + season + " group by userID) as PS using (userID, weekNumber, season) set PlayoffResult.points=PS.points")

	# update wild card stats
	if( week == "19" ):
		# grab the game data
		cur.execute("select homeScore+awayScore, status from Game where season=" + season + " and weekNumber=19 order by tieBreakOrder desc, gameTime desc")
		gameScores = cur.fetchall()

		# now update the conferences individually
		cur.execute("update SeasonResult join PlayoffResult using (userID, season) set advances=firstRoundBye where season=" + season + " and weekNumber=19")
		cur.execute("select distinct(confID) from SeasonResult join Division using (divID) where season=" + season )
		conferences = cur.fetchall()
		for conf in conferences:
			# grab the top 16
			winnerCount = 0
			winnerIDs = []
			currRow = [0,50000,0,0,0,0,0,0,0,0]
			cur.execute("select userID, PlayoffResult.points, abs(tieBreaker1 - " + str(gameScores[0][0]) + ") as tb1, tieBreaker1, abs(tieBreaker2 - " + str(gameScores[1][0]) + ") as tb2, tieBreaker2, abs(tieBreaker3 - " + str(gameScores[2][0]) + ") as tb3, tieBreaker3, abs(tieBreaker4 - " + str(gameScores[3][0]) + ") as tb4, tieBreaker4, abs(tieBreaker5 - " + str(gameScores[4][0]) + ") as tb5, tieBreaker5, abs(tieBreaker6 - " + str(gameScores[5][0]) + ") as tb6, tieBreaker6 from PlayoffResult join SeasonResult using (userID, season) join Division using (divID) where weekNumber=19 and season=" + season + " and confID=" + str(conf[0]) + " and firstRoundBye='N' order by points desc, tb1 asc, tieBreaker1 asc, tb2 asc, tieBreaker2 asc, tb3 asc, tieBreaker3 asc, tb4 asc, tieBreaker4 asc, tb5 asc, tieBreaker5 asc, tb6 asc, tieBreaker6 asc, userID")
			leaders = cur.fetchall()
			for player in leaders:
				# see if this row is ahead of the current one
				if winnerCount < 16 and (player[1] < currRow[1] or (player[1] == currRow[1] and gameScores[0][1]  == 3 and (player[2] > currRow[2] or (player[2] == currRow[2] and (player[3] > currRow[3] or (player[3] == currRow[3] and (player[4] > currRow[4] or (player[4] == currRow[4] and (player[5] > currRow[5] or (player[5] == currRow[5] and (player[6] > currRow[6] or (player[6] == currRow[6] and (player[7] > currRow[7] or (player[7] == currRow[7] and (player[8] > currRow[8] or (player[8] == currRow[8] and (player[9] > currRow[9] or (player[9] == currRow[9] and (player[10] > currRow[10] or (player[10] == currRow[10] and (player[11] > currRow[11] or (player[11] == currRow[11] and (player[12] > currRow[12] or (player[12] == currRow[12] and (player[13] > currRow[13]))))))))))))))))))))))))):
					winnerCount = winnerCount + len(winnerIDs)
					for winner in winnerIDs:
						cur.execute("update PlayoffResult set advances=if(" + str(winnerCount) + " <= 16, 'Y', 'R') where weekNumber=19 and season=" + season + " and userID=" + str(winner))
					winnerIDs = [player[0]]
					currRow = player
				else:
					winnerIDs.append( player[0] )
			# tied guys
			if( winnerCount < 16 ):
				winnerCount = winnerCount + len(winnerIDs)
				for winner in winnerIDs:
					cur.execute("update PlayoffResult set advances=if(" + str(winnerCount) + " <= 16, 'Y', 'R') where weekNumber=19 and season=" + season + " and userID=" + str(winner))

		# see if the round is final
		cur.execute("select sum(status<" + str(FINAL) + ") from Game where season=" + season + " and weekNumber=19")
		num = cur.fetchall()
		if num[0][0] == 0:
			# see how many of these there are
			cur.execute("select count(*) from PlayoffResult where season=" + season + " and weekNumber=19 and advances='Y'")
			playoffCount = cur.fetchall()[0][0]

			# fix their week 19 scores
			cur.execute("update PlayoffResult as PRX join PlayoffResult as PR19 using (userID, season) set PRX.prevWeek1=if(PR19.advances='Y', PR19.points, -(1+PR19.points)), PRX.advances=if(PR19.advances='N', 'N', 'R') where PR19.weekNumber=19 and PRX.weekNumber>19 and season=" + season)

			# add in the needed results
			cur.execute("insert into Pick (userID, gameID, points) select userID, gameID, 0 from PlayoffResult join Game using (weekNumber, season) where weekNumber=20 and prevWeek1>=0 and season=" + str(season))
        	        #cur.execute("update Pick set points=2 where gameID=(select gameID from Game where weekNumber=19 and season=" + str(season) + " order by gameTime desc limit 1,1)")
                	#cur.execute("update Pick set points=3 where gameID=(select gameID from Game where weekNumber=19 and season=" + str(season) + " order by gameTime desc limit 2,1)")
	                #cur.execute("update Pick set points=4 where gameID=(select gameID from Game where weekNumber=19 and season=" + str(season) + " order by gameTime desc limit 3,1)")

	# update divisional stats
	if( week == "20" ):
		# grab the game data
		cur.execute("select homeScore+awayScore, status from Game where season=" + season + " and weekNumber=20 order by tieBreakOrder desc, gameTime desc")
		gameScores = cur.fetchall()

		# now update the conferences individually
		cur.execute("update SeasonResult join PlayoffResult using (userID, season) set advances='N' where season=" + season + " and weekNumber=20")
		cur.execute("select distinct(confID) from SeasonResult join Division using (divID) where season=" + season )
		conferences = cur.fetchall()
		for conf in conferences:
			# grab the top 10
			winnerCount = 0
			winnerIDs = []
			currRow = [0,50000,0,0,0,0,0,0,0,0]
			cur.execute("select userID, PlayoffResult.points, abs(tieBreaker1 - " + str(gameScores[0][0]) + ") as tb1, tieBreaker1, abs(tieBreaker2 - " + str(gameScores[1][0]) + ") as tb2, tieBreaker2, abs(tieBreaker3 - " + str(gameScores[2][0]) + ") as tb3, tieBreaker3, abs(tieBreaker4 - " + str(gameScores[3][0]) + ") as tb4, tieBreaker4 from PlayoffResult join SeasonResult using (userID, season) join Division using (divID) where weekNumber=20 and season=" + season + " and confID=" + str(conf[0]) + " and prevWeek1>=0 order by points desc, tb1 asc, tieBreaker1 asc, tb2 asc, tieBreaker2 asc, tb3 asc, tieBreaker3 asc, tb4 asc, tieBreaker4 asc, userID")
			leaders = cur.fetchall()
			for player in leaders:
				# see if this row is ahead of the current one
				if winnerCount < 10 and (player[1] < currRow[1] or (player[1] == currRow[1] and gameScores[0][1] == 3 and (player[2] > currRow[2] or (player[2] == currRow[2] and (player[3] > currRow[3] or (player[3] == currRow[3] and (player[4] > currRow[4] or (player[4] == currRow[4] and (player[5] > currRow[5] or (player[5] == currRow[5] and (player[6] > currRow[6] or (player[6] == currRow[6] and (player[7] > currRow[7] or (player[7] == currRow[7] and (player[8] > currRow[8] or (player[8] == currRow[8] and (player[9] > currRow[9]))))))))))))))))):
					winnerCount = winnerCount + len(winnerIDs)
					for winner in winnerIDs:
						cur.execute("update PlayoffResult set advances=if(" + str(winnerCount) + " <= 10, 'Y', 'R') where weekNumber=20 and season=" + season + " and userID=" + str(winner))
					winnerIDs = [player[0]]
					currRow = player
				else:
					winnerIDs.append( player[0] )
			# tied guys
			if( winnerCount < 10 ):
				winnerCount = winnerCount + len(winnerIDs)
				for winner in winnerIDs:
					cur.execute("update PlayoffResult set advances=if(" + str(winnerCount) + " <= 10, 'Y', 'R') where weekNumber=20 and season=" + season + " and userID=" + str(winner))
				

		# see if the round is final
		cur.execute("select sum(status<" + str(FINAL) + ") from Game where season=" + season + " and weekNumber=20")
		num = cur.fetchall()
		if num[0][0] == 0:
			# see how many of these there are
			cur.execute("select count(*) from PlayoffResult where season=" + season + " and weekNumber=20 and advances='Y'")
			playoffCount = cur.fetchall()[0][0]

			# fix their week 20 scores
			cur.execute("update PlayoffResult as PRX join PlayoffResult as PR20 using (userID, season) set PRX.prevWeek2=if(PR20.advances='Y', PR20.points, -(1+PR20.points)), PRX.advances=if(PR20.advances='N', 'N', 'R') where PR20.weekNumber=20 and PRX.weekNumber>20 and season=" + season)

			# add in the needed results
			cur.execute("insert into Pick (userID, gameID, points) select userID, gameID, 1 from PlayoffResult join Game using (weekNumber, season) where weekNumber=21 and prevWeek2>=0 and season=" + str(season))
			cur.execute("insert into Pick (userID, gameID, points, type) select userID, gameID, 0, 'winner2Q' from PlayoffResult join Game using (weekNumber, season) where weekNumber=21 and prevWeek2>=0 and season=" + str(season))
        	        #cur.execute("update Pick set points=2 where gameID=(select gameID from Game where weekNumber=20 and season=" + str(season) + " order by gameTime desc limit 1,1) and type='winner2Q' ")
                	#cur.execute("update Pick set points=3 where gameID=(select gameID from Game where weekNumber=20 and season=" + str(season) + " order by gameTime desc limit 0,1) and type='winner' ")
	                #cur.execute("update Pick set points=4 where gameID=(select gameID from Game where weekNumber=20 and season=" + str(season) + " order by gameTime desc limit 1,1) and type='winner' ")

	# update conference stats
	if( week == "21" ):
		# grab the game data
		cur.execute("select homeScore+awayScore, homeScore2Q+awayScore2Q, status from Game where season=" + season + " and weekNumber=21 order by tieBreakOrder desc, gameTime desc")
		gameScores = cur.fetchall()

		# now update the conferences individually
		cur.execute("update SeasonResult join PlayoffResult using (userID, season) set advances='N' where season=" + season + " and weekNumber=21")
		cur.execute("select distinct(confID) from SeasonResult join Division using (divID) where season=" + season )
		conferences = cur.fetchall()
		for conf in conferences:
			# grab the top 5
			winnerCount = 0
			winnerIDs = []
			currRow = [0,50000,0,0,0,0,0,0,0,0]
			cur.execute("select userID, PlayoffResult.points, abs(tieBreaker1 - " + str(gameScores[0][0]) + ") as tb1, tieBreaker1, abs(tieBreaker2 - " + str(gameScores[1][0]) + ") as tb2, tieBreaker2, abs(tieBreaker3 - " + str(gameScores[0][1]) + ") as tb3, tieBreaker3, abs(tieBreaker4 - " + str(gameScores[1][1]) + ") as tb4, tieBreaker4 from PlayoffResult join SeasonResult using (userID, season) join Division using (divID) where weekNumber=21 and season=" + season + " and confID=" + str(conf[0]) + " and prevWeek2>=0 order by points desc, tb1 asc, tieBreaker1 asc, tb2 asc, tieBreaker2 asc, tb3 asc, tieBreaker3 asc, tb4 asc, tieBreaker4 asc, userID")
			leaders = cur.fetchall()
			for player in leaders:
				# see if this row is ahead of the current one
				if winnerCount < 5 and (player[1] < currRow[1] or (player[1] == currRow[1] and gameScores[0][2] == 3 and (player[2] > currRow[2] or (player[2] == currRow[2] and (player[3] > currRow[3] or (player[3] == currRow[3] and (player[4] > currRow[4] or (player[4] == currRow[4] and (player[5] > currRow[5] or (player[5] == currRow[5] and (player[6] > currRow[6] or (player[6] == currRow[6] and (player[7] > currRow[7] or (player[7] == currRow[7] and (player[8] > currRow[8] or (player[8] == currRow[8] and (player[9] > currRow[9]))))))))))))))))):
					winnerCount = winnerCount + len(winnerIDs)
					for winner in winnerIDs:
						cur.execute("update PlayoffResult set advances=if(" + str(winnerCount) + " <= 5, 'Y', 'R') where weekNumber=21 and season=" + season + " and userID=" + str(winner))
					winnerIDs = [player[0]]
					currRow = player
				else:
					winnerIDs.append( player[0] )
			# tied guys
			if( winnerCount < 5 ):
				winnerCount = winnerCount + len(winnerIDs)
				for winner in winnerIDs:
					cur.execute("update PlayoffResult set advances=if(" + str(winnerCount) + " <= 5, 'Y', 'R') where weekNumber=21 and season=" + season + " and userID=" + str(winner))
				

		# see if the round is final
		cur.execute("select sum(status<" + str(FINAL) + ") from Game where season=" + season + " and weekNumber=21")
		num = cur.fetchall()
		if num[0][0] == 0:
			# see how many of these there are
			cur.execute("select count(*) from PlayoffResult where season=" + season + " and weekNumber=21 and advances='Y'")
			playoffCount = cur.fetchall()[0][0]

			# fix their week 21 scores
			cur.execute("update PlayoffResult as PRX join PlayoffResult as PR21 using (userID, season) set PRX.prevWeek3=if(PR21.advances='Y', PR21.points, -(1+PR21.points)), PRX.advances=if(PR21.advances='N', 'N', 'R') where PR21.weekNumber=21 and PRX.weekNumber>21 and season=" + season)

			# add in the needed results
			cur.execute("insert into Pick (userID, gameID, points, type) select userID, gameID, 0, 'winner' from PlayoffResult join Game using (weekNumber, season) where weekNumber=23 and prevWeek3>=0 and season=" + str(season))
			cur.execute("insert into Pick (userID, gameID, points, type) select userID, gameID, 9, 'winner3Q' from PlayoffResult join Game using (weekNumber, season) where weekNumber=23 and prevWeek3>=0 and season=" + str(season))
			cur.execute("insert into Pick (userID, gameID, points, type) select userID, gameID, 8, 'winner2Q' from PlayoffResult join Game using (weekNumber, season) where weekNumber=23 and prevWeek3>=0 and season=" + str(season))
			cur.execute("insert into Pick (userID, gameID, points, type) select userID, gameID, 7, 'winner1Q' from PlayoffResult join Game using (weekNumber, season) where weekNumber=23 and prevWeek3>=0 and season=" + str(season))
			cur.execute("insert into Pick (userID, gameID, points, type) select userID, gameID, 5, 'passYds' from PlayoffResult join Game using (weekNumber, season) where weekNumber=23 and prevWeek3>=0 and season=" + str(season))
			cur.execute("insert into Pick (userID, gameID, points, type) select userID, gameID, 3, 'passYds2Q' from PlayoffResult join Game using (weekNumber, season) where weekNumber=23 and prevWeek3>=0 and season=" + str(season))
			cur.execute("insert into Pick (userID, gameID, points, type) select userID, gameID, 5, 'rushYds' from PlayoffResult join Game using (weekNumber, season) where weekNumber=23 and prevWeek3>=0 and season=" + str(season))
			cur.execute("insert into Pick (userID, gameID, points, type) select userID, gameID, 3, 'rushYds2Q' from PlayoffResult join Game using (weekNumber, season) where weekNumber=23 and prevWeek3>=0 and season=" + str(season))
			cur.execute("insert into Pick (userID, gameID, points, type) select userID, gameID, 2, 'TDs' from PlayoffResult join Game using (weekNumber, season) where weekNumber=23 and prevWeek3>=0 and season=" + str(season))
			cur.execute("insert into Pick (userID, gameID, points, type) select userID, gameID, 1, 'TDs2Q' from PlayoffResult join Game using (weekNumber, season) where weekNumber=23 and prevWeek3>=0 and season=" + str(season))

	# update super bowl stats
	if( week == "23" ):
		# grab the game score
		cur.execute("select if(homeScore>awayScore, homeTeam, if(homeScore<awayScore, awayTeam, 'TIE')) from Game where season=" + season + " and weekNumber=23 order by tieBreakOrder desc, gameTime desc")
		leader = cur.fetchall()

		# all we worry about here is whether they picked the correct winner or not
		cur.execute("update PlayoffResult join Game using (season, weekNumber) join Pick using (userID, gameID) set advances=if('" + leader[0][0] + "' = 'TIE', 'R', if(winner='" + leader[0][0] + "', 'Y', 'N')) where weekNumber=23 and type='winner' and season=" + str(season))

# define the update function
def updateConsolationStats(season):
	# grab the game score
	cur.execute("select gameID from Game where season=" + str(season) + " and weekNumber>18 order by gameID limit 0,1");
	wc1AFC = cur.fetchall()
	
	# reset playoff pool stats for this week
	cur.execute("update ConsolationResult join Game as WC1AFC on (WC1AFC.season=ConsolationResult.season and WC1AFC.gameID=(" + str(wc1AFC[0][0]) + "+0)) join Game as WC2AFC on (WC2AFC.season=ConsolationResult.season and WC2AFC.gameID=(" + str(wc1AFC[0][0]) + "+1)) join Game as WC3AFC on (WC3AFC.season=ConsolationResult.season and WC3AFC.gameID=(select gameID from Game where season=(" + str(wc1AFC[0][0]) + "+2)) join Game as WC1NFC on (WC1NFC.season=ConsolationResult.season and WC1NFC.gameID=(select gameID from Game where season=(" + str(wc1AFC[0][0]) + "+3)) join Game as WC2NFC on (WC2NFC.season=ConsolationResult.season and WC2NFC.gameID=(select gameID from Game where season=(" + str(wc1AFC[0][0]) + "+4)) join Game as WC3NFC on (WC3NFC.season=ConsolationResult.season and WC3NFC.gameID=(select gameID from Game where season=(" + str(wc1AFC[0][0]) + "+5)) join Game as DIV1AFC on (DIV1AFC.season=ConsolationResult.season and DIV1AFC.gameID=(select gameID from Game where season=(" + str(wc1AFC[0][0]) + "+6)) join Game as DIV2AFC on (DIV2AFC.season=ConsolationResult.season and DIV2AFC.gameID=(select gameID from Game where season=(" + str(wc1AFC[0][0]) + "+7)) join Game as DIV1NFC on (DIV1NFC.season=ConsolationResult.season and DIV1NFC.gameID=(select gameID from Game where season=(" + str(wc1AFC[0][0]) + "+8)) join Game as DIV2NFC on (DIV2NFC.season=ConsolationResult.season and DIV2NFC.gameID=(select gameID from Game where season=(" + str(wc1AFC[0][0]) + "+9)) join Game as CONFAFC on (CONFAFC.season=ConsolationResult.season and CONFAFC.gameID=(select gameID from Game where season=(" + str(wc1AFC[0][0]) + "+10)) join Game as CONFNFC on (CONFNFC.season=ConsolationResult.season and CONFNFC.gameID=(select gameID from Game where season=(" + str(wc1AFC[0][0]) + "+11)) join Game as SB on (SB.season=ConsolationResult.season and SB.gameID=(select gameID from Game where season=" + str(season) + " and weekNumber>18 order by gameID limit 12,1)) set points = if((wc1AFC=WC1AFC.homeTeam and WC1AFC.homeScore>WC1AFC.awayScore) or (wc1AFC=WC1AFC.awayTeam and WC1AFC.awayScore>WC1AFC.homeScore), 1, 0) + if((wc2AFC=WC2AFC.homeTeam and WC2AFC.homeScore>WC2AFC.awayScore) or (wc2AFC=WC2AFC.awayTeam and WC2AFC.awayScore>WC2AFC.homeScore), 1, 0) + if((wc3AFC=WC3AFC.homeTeam and WC3AFC.homeScore>WC3AFC.awayScore) or (wc3AFC=WC3AFC.awayTeam and WC3AFC.awayScore>WC3AFC.homeScore), 1, 0) + if((wc1NFC=WC1NFC.homeTeam and WC1NFC.homeScore>WC1NFC.awayScore) or (wc1NFC=WC1NFC.awayTeam and WC1NFC.awayScore>WC1NFC.homeScore), 1, 0) + if((wc2NFC=WC2NFC.homeTeam and WC2NFC.homeScore>WC2NFC.awayScore) or (wc2NFC=WC2NFC.awayTeam and WC2NFC.awayScore>WC2NFC.homeScore), 1, 0) + if((wc3NFC=WC3NFC.homeTeam and WC3NFC.homeScore>WC3NFC.awayScore) or (wc3NFC=WC3NFC.awayTeam and WC3NFC.awayScore>WC3NFC.homeScore), 1, 0) + if((div1AFC=DIV1AFC.homeTeam and DIV1AFC.homeScore>DIV1AFC.awayScore) or (div1AFC=DIV1AFC.awayTeam and DIV1AFC.awayScore>DIV1AFC.homeScore) or (div1AFC=DIV2AFC.homeTeam and DIV2AFC.homeScore>DIV2AFC.awayScore) or (div1AFC=DIV2AFC.awayTeam and DIV2AFC.awayScore>DIV2AFC.homeScore), 2, 0) + if((div2AFC=DIV2AFC.homeTeam and DIV2AFC.homeScore>DIV2AFC.awayScore) or (div2AFC=DIV2AFC.awayTeam and DIV2AFC.awayScore>DIV2AFC.homeScore) or (div2AFC=DIV1AFC.homeTeam and DIV1AFC.homeScore>DIV1AFC.awayScore) or (div2AFC=DIV1AFC.awayTeam and DIV1AFC.awayScore>DIV1AFC.homeScore), 2, 0) + if((div1NFC=DIV1NFC.homeTeam and DIV1NFC.homeScore>DIV1NFC.awayScore) or (div1NFC=DIV1NFC.awayTeam and DIV1NFC.awayScore>DIV1NFC.homeScore) or (div1NFC=DIV2NFC.homeTeam and DIV2NFC.homeScore>DIV2NFC.awayScore) or (div1NFC=DIV2NFC.awayTeam and DIV2NFC.awayScore>DIV2NFC.homeScore), 2, 0) + if((div2NFC=DIV2NFC.homeTeam and DIV2NFC.homeScore>DIV2NFC.awayScore) or (div2NFC=DIV2NFC.awayTeam and DIV2NFC.awayScore>DIV2NFC.homeScore) or (div2NFC=DIV1NFC.homeTeam and DIV1NFC.homeScore>DIV1NFC.awayScore) or (div2NFC=DIV1NFC.awayTeam and DIV1NFC.awayScore>DIV1NFC.homeScore), 2, 0) + if((confAFC=CONFAFC.homeTeam and CONFAFC.homeScore>CONFAFC.awayScore) or (confAFC=CONFAFC.awayTeam and CONFAFC.awayScore>CONFAFC.homeScore), 4, 0) + if((confNFC=CONFNFC.homeTeam and CONFNFC.homeScore>CONFNFC.awayScore) or (confNFC=CONFNFC.awayTeam and CONFNFC.awayScore>CONFNFC.homeScore), 4, 0) + if((superBowl=SB.homeTeam and SB.homeScore>SB.awayScore) or (superBowl=SB.awayTeam and SB.awayScore>SB.homeScore), 8, 0), picksCorrect=if((wc1AFC=WC1AFC.homeTeam and WC1AFC.homeScore>WC1AFC.awayScore) or (wc1AFC=WC1AFC.awayTeam and WC1AFC.awayScore>WC1AFC.homeScore), 1, 0) + if((wc2AFC=WC2AFC.homeTeam and WC2AFC.homeScore>WC2AFC.awayScore) or (wc2AFC=WC2AFC.awayTeam and WC2AFC.awayScore>WC2AFC.homeScore), 1, 0) + if((wc3AFC=WC3AFC.homeTeam and WC3AFC.homeScore>WC3AFC.awayScore) or (wc3AFC=WC3AFC.awayTeam and WC3AFC.awayScore>WC3AFC.homeScore), 1, 0) + if((wc1NFC=WC1NFC.homeTeam and WC1NFC.homeScore>WC1NFC.awayScore) or (wc1NFC=WC1NFC.awayTeam and WC1NFC.awayScore>WC1NFC.homeScore), 1, 0) + if((wc2NFC=WC2NFC.homeTeam and WC2NFC.homeScore>WC2NFC.awayScore) or (wc2NFC=WC2NFC.awayTeam and WC2NFC.awayScore>WC2NFC.homeScore), 1, 0) + if((wc3NFC=WC3NFC.homeTeam and WC3NFC.homeScore>WC3NFC.awayScore) or (wc3NFC=WC3NFC.awayTeam and WC3NFC.awayScore>WC3NFC.homeScore), 1, 0) + if((div1AFC=DIV1AFC.homeTeam and DIV1AFC.homeScore>DIV1AFC.awayScore) or (div1AFC=DIV1AFC.awayTeam and DIV1AFC.awayScore>DIV1AFC.homeScore) or (div1AFC=DIV2AFC.homeTeam and DIV2AFC.homeScore>DIV2AFC.awayScore) or (div1AFC=DIV2AFC.awayTeam and DIV2AFC.awayScore>DIV2AFC.homeScore), 1, 0) + if((div2AFC=DIV2AFC.homeTeam and DIV2AFC.homeScore>DIV2AFC.awayScore) or (div2AFC=DIV2AFC.awayTeam and DIV2AFC.awayScore>DIV2AFC.homeScore) or (div2AFC=DIV1AFC.homeTeam and DIV1AFC.homeScore>DIV1AFC.awayScore) or (div2AFC=DIV1AFC.awayTeam and DIV1AFC.awayScore>DIV1AFC.homeScore), 1, 0) + if((div1NFC=DIV1NFC.homeTeam and DIV1NFC.homeScore>DIV1NFC.awayScore) or (div1NFC=DIV1NFC.awayTeam and DIV1NFC.awayScore>DIV1NFC.homeScore) or (div1NFC=DIV2NFC.homeTeam and DIV2NFC.homeScore>DIV2NFC.awayScore) or (div1NFC=DIV2NFC.awayTeam and DIV2NFC.awayScore>DIV2NFC.homeScore), 1, 0) + if((div2NFC=DIV2NFC.homeTeam and DIV2NFC.homeScore>DIV2NFC.awayScore) or (div2NFC=DIV2NFC.awayTeam and DIV2NFC.awayScore>DIV2NFC.homeScore) or (div2NFC=DIV1NFC.homeTeam and DIV1NFC.homeScore>DIV1NFC.awayScore) or (div2NFC=DIV1NFC.awayTeam and DIV1NFC.awayScore>DIV1NFC.homeScore), 1, 0) + if((confAFC=CONFAFC.homeTeam and CONFAFC.homeScore>CONFAFC.awayScore) or (confAFC=CONFAFC.awayTeam and CONFAFC.awayScore>CONFAFC.homeScore), 1, 0) + if((confNFC=CONFNFC.homeTeam and CONFNFC.homeScore>CONFNFC.awayScore) or (confNFC=CONFNFC.awayTeam and CONFNFC.awayScore>CONFNFC.homeScore), 1, 0) + if((superBowl=SB.homeTeam and SB.homeScore>SB.awayScore) or (superBowl=SB.awayTeam and SB.awayScore>SB.homeScore), 1, 0) where ConsolationResult.season=" + season)
