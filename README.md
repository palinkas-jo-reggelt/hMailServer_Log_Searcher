# hMailServer Log Searcher
 Search hMailServer logs by keyword. Keyword can be string, IP, date/time, etc.

 The script loops through all files in hMailServer's Logs directory (path found via API), reads each line and if a matching keyword is found, displays the line and the log filename. 

# Instructions
 Copy config.dist.php to config.php and fill in the variables.