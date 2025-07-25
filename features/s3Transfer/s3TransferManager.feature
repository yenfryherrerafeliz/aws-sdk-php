@s3-transfer-manager @integ
Feature: S3 Transfer Manager
  S3 Transfer Manager should successfully do:
  - object uploads
  - object multipart uploads
  - object copy
  - object multipart copy
  - object downloads
  - object multipart downloads
  - directory object uploads
  - directory object downloads

  Scenario Outline: Successfully does a single object copy
    Given I have an object <object> with content <content> in a source bucket
    When I copy the object <object> to a destination bucket using the S3 Transfer Manager
    Then the object <object> should exist in the destination bucket and its content should be <content>

    Examples:
      | object                 | content           |
      | single-copy-test-1.txt    | Test content #1   |
      | single-copy-test-2.txt    | Test content #2   |
      | single-copy-test-3.txt    | Test content #3   |

  Scenario Outline: Successfully does a multipart object copy
    Given I have an object <object> where its content size is <objectsize>
    When I copy the object <object> with part size <partsize> to a destination bucket using the S3 Transfer Manager
    Then the object <object> exists in the destination bucket with <partnum> parts and its size must be <objectsize>

    Examples:
      | object                 | objectsize  | partsize  | partnum |
      | multipart-copy-test-1.txt    | 10485760  | 5242880   | 2       |
      | multipart-copy-test-2.txt    | 24117248  | 5242880   | 5       |
      | multipart-copy-test-3.txt    | 24117248  | 8388608   | 3       |

  Scenario Outline: Successfully cleans up uploaded part during multipart copy failure
    Given I have an object <object> where its content size is <objectsize>
    When I attempt to copy the object <object> with part size <partsize> to a destination bucket but fail on part <failPart>
    Then there should be no leftover multipart uploads for object <object> in the destination bucket

    Examples:
      | object                 | objectsize   | partsize  | failPart |
      | abort-test-1.txt    | 10485760   | 5242880   | 1        |
      | abort-test-2.txt    | 24117248   | 5242880   | 3        |
      | abort-test-3.txt    | 24117248   | 8388608   | 2O        |

  Scenario Outline: Successfully does a single file upload
    Given I have a file <filename> with content <content>
    When I upload the file <filename> to a test bucket using the s3 transfer manager
    Then the file <filename> should exist in the test bucket and its content should be <content>

    Examples:
      | filename     | content         |
      | myfile-test-1-1.txt   | Test content #1 |
      | myfile-test-1-2.txt | Test content #2 |
      | myfile-test-1-3.txt | Test content #3 |


  Scenario Outline: Successfully does a single upload from a stream
    Given I have a stream with content <content>
    When I do the upload to a test bucket with key <key>
    Then the object <key>, once downloaded from the test bucket, should match the content <content>
    Examples:
      | content | key |
      | "This is a test text - 1" | myfile-test-2-1.txt |
      | "This is a test text - 2" | myfile-test-2-2.txt |
      | "This is a test text - 3" | myfile-test-2-3.txt |

  Scenario Outline: Successfully do multipart object upload from file
    Given I have a file with name <filename> where its content's size is <filesize>
    When I do upload this file with name <filename> with the specified part size of <partsize>
    Then the object with name <filename> should have a total of <partnum> parts and its size must be <filesize>
    Examples:
    | filename | filesize | partsize | partnum |
    | myfile-test-3-1.txt   | 10485760 | 5242880  | 2       |
    | myfile-test-3-2.txt   | 24117248 | 5242880  | 5       |
    | myfile-test-3-3.txt   | 24117248 | 8388608  | 3       |

  Scenario Outline: Successfully do multipart object upload from streams
    Given I have want to upload a stream of size <filesize>
    When I do upload this stream with name <filename> and the specified part size of <partsize>
    Then the object with name <filename> should have a total of <partnum> parts and its size must be <filesize>
    Examples:
      | filename | filesize | partsize | partnum |
      | myfile-test-4-1.txt   | 10485760 | 5242880  | 2       |
      | myfile-test-4-2.txt   | 24117248 | 5242880  | 5       |
      | myfile-test-4-3.txt   | 24117248 | 8388608  | 3       |

  Scenario Outline: Does single object upload with custom checksum
    Given I have a file with name <filename> and its content is <content>
    When I upload this file with name <filename> by providing a custom checksum algorithm <checksum_algorithm>
    Then the checksum from the object with name <filename> should be equals to the calculation of the object content with the checksum algorithm <checksum_algorithm>
    Examples:
    | filename | content | checksum_algorithm |
    | myfile-test-5-1.txt   | This is a test file content #1 | crc32 |
    | myfile-test-5-2.txt   | This is a test file content #2 | crc32c |
    | myfile-test-5-3.txt   | This is a test file content #3 | sha256 |
    | myfile-test-5-4.txt   | This is a test file content #4 | sha1 |

  Scenario Outline: Does single object download
    Given I have an object in S3 with name <filename> and its content is <content>
    When I do a download of the object with name <filename>
    Then the object with name <filename> should have been downloaded and its content should be <content>
    Examples:
      | filename | content |
      | myfile-test-6-1.txt   | This is a test file content #1 |
      | myfile-test-6-2.txt   | This is a test file content #2 |
      | myfile-test-6-3.txt   | This is a test file content #3 |

  Scenario Outline: Successfully does multipart object download
    Given I have an object in S3 with name <filename> and its size is <filesize>
    When I download the object with name <filename> by using the <download_type> multipart download type
    Then the content size for the object with name <filename> should be <filesize>
    Examples:
      | filename | filesize  | download_type |
      | myfile-test-7-1.txt   |  20971520 | rangeGet         |
      | myfile-test-7-2.txt   | 28311552  | rangeGet         |
      | myfile-test-7-3.txt  |  12582912 | rangeGet         |
      | myfile-test-7-4.txt   |  20971520 | partGet           |
      | myfile-test-7-5.txt   | 28311552  | partGet           |
      | myfile-test-7-6.txt   |  12582912 | partGet           |

  Scenario Outline: Successfully does directory upload
    Given I have a directory <directory> with <numfile> files that I want to upload
    When I upload this directory <directory>
    Then the files from this directory <directory> where its count should be <numfile> should exist in the bucket
    Examples:
      | directory | numfile |
      | directory-test-1-1 | 10 |
      | directory-test-1-2 | 3 |
      | directory-test-1-3 | 25 |
      | directory-test-1-4 | 1 |

  Scenario Outline: Successfully does a directory download
    Given I have a total of <numfile> objects in a bucket prefixed with <directory>
    When I download all of them into the directory <directory>
    Then the objects <numfile> should exist as files within the directory <directory>
    Examples:
      | numfile | directory |
      | 15 | directory-test-2-1 |
      | 12 | directory-test-2-2 |
      | 1 | directory-test-2-3 |
      | 30 | directory-test-2-4 |

  Scenario Outline: Abort a multipart upload
    Given I am uploading the file <file> with size <size>
    When I upload the file <file> using multipart upload and fails at part number <partNumberFail>
    Then The multipart upload should have been aborted for file <file>
    Examples:
      | file | size | partNumberFail |
      | abort-file-1.txt | 1024 * 1024 * 20 | 3 |
      | abort-file-2.txt | 1024 * 1024 * 40 | 5 |
      | abort-file-3.txt | 1024 * 1024 * 10 | 1 |