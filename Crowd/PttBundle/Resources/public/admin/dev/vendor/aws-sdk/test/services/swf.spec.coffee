helpers = require('../helpers')
AWS = helpers.AWS

describe 'AWS.SWF', ->
  it 'is also AWS.SimpleWorkflow', ->
    expect(AWS.SWF).to.equal(AWS.SimpleWorkflow)
