
Usage:
  php morphine generate TYPE MODULE:RESOURCE [-s|-t|-a|-u|-p] [--smart|--scaff]

Types:
  module            - Generates an empty module
  workflow          - Generates an empty workflow
  model             - Generates a model
  dao               - Generates a DAO
  help              - Displays this help file

Options:
  -s                - Set a custom source location
  -t                - Set a custom target location
  -a                - Adapter driver
  -u                - Database username
  --smart           - Smart generation mode
  --scaff           - Scaffolding mode

Examples:
  php morphine generate dao user:account -s mysql:edgeyo:user_account -u root@localhost

    - will generate a UserAccountMysql in [base]/module/user/dao/account-mysql.php
      by referring to the table "user_account" in the MySQL database "edgeyo",
      using the login credentials "root@localhost" and the password

  php morphine generate workflow user:account -s dao:user:account-mysql
    - will generate a UserAccountWorkflow in [base]/module/workflow/account.php
      by referring to the DAO "UserAccountMysql"
