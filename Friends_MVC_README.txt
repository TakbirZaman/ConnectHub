Friends page refactor (MVC)
- controller/FriendsController.php added
- model/Friend.php: helper methods appended (incomingRequestsList, friendsList, searchUsersWithRelation, etc.)
- controller/search_friends.php updated to use Friend model
- controller/friend_action.php updated, safe notify()
- view/friends.php now contains no SQL
- index.php now routes page=friends to controller/FriendsController.php
- CSS appended in view/style.css (friend-row + .btn styles)

Nothing else changed. Make sure database has 'notifications' table if you want alerts.
