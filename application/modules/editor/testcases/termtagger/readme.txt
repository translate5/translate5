****************
Testing openTMStermTagger
****************
- Tests are build upon PHPUnit
- To execute tests, 
    -- you need PHPUnit 4.7 installed as phar-archive, as described at phpunit.de
    -- you need to login with a user, that has the role "admin" assigned
    -- you need to call the URI http://translate5URI/editor/test/termtagger
    -- you need at least one termTagger configured as GUI-termTagger in Zf_configuration

- The test will look for files with the ending ".testcase" in the folder /application/modules/editor/testcases/termtagger

- These testcase-files are automatically validated against termtaggerTestCaseSchema.xsd. Please see the testcase-folder for example-files.

- For each testcase-file the test will launch a new PHPUnit-Testsuite

- The input > source and the input > target contents will be send to the termTagger and 
  compared with the expectedOutput > source and expectedOutput > target

- The comparision will be done by the assertion specified in the type-attribute of the assertion-tag in the testcase-file.
  This assertion has to be defined in editor_Test_Termtagger as methods assertionnameSource and assertionnameTarget, where as "assertionname stands for the content of the attribute

- the results for each .testcase-file will be displayed as browser output

- For each .testcase-file behind the surface the test will 
    -- create a real translate5-task
    -- process the termTagging upon it
    -- compare the results
    -- delete the translate5-task again
    -- display the results
=> This means, by executing the termTagger-tests, most of the import-procedures are tested as well.

TIP: *.testcase is registered as a new file format of translate5. If you want to see your tests in translate5 GUI, just check them in the normal way.