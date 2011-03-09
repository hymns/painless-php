
Usage:
  php morphine execute [type].[module-name].[component-name].[function-camel] [param] [param] [param]...

Types:
  module            - Executes a module
  workflow          - Executes a workflow
  model             - Executes a model
  view              - Executes a view
  help              - Displays this help file

Examples:
  php morphine execute model.user.profile.getUser {"id":"12"} [12,12]
    