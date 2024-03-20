<?php
// This file was auto-generated from sdk-root/src/data/workspaces-thin-client/2023-08-22/docs-2.json
return [ 'version' => '2.0', 'service' => '<p>Amazon WorkSpaces Thin Client is an affordable device built to work with Amazon Web Services End User Computing (EUC) virtual desktops to provide users with a complete cloud desktop solution. WorkSpaces Thin Client is a compact device designed to connect up to two monitors and USB devices like a keyboard, mouse, headset, and webcam. To maximize endpoint security, WorkSpaces Thin Client devices do not allow local data storage or installation of unapproved applications. The WorkSpaces Thin Client device ships preloaded with device management software.</p> <p>You can use these APIs to complete WorkSpaces Thin Client tasks, such as creating environments or viewing devices. For more information about WorkSpaces Thin Client, including the required permissions to use the service, see the <a href="https://docs.aws.amazon.com/workspaces-thin-client/latest/ag/">Amazon WorkSpaces Thin Client Administrator Guide</a>. For more information about using the Command Line Interface (CLI) to manage your WorkSpaces Thin Client resources, see the <a href="https://docs.aws.amazon.com/cli/latest/reference/workspaces-thin-client/index.html">WorkSpaces Thin Client section of the CLI Reference</a>.</p>', 'operations' => [ 'CreateEnvironment' => '<p>Creates an environment for your thin client devices.</p>', 'DeleteDevice' => '<p>Deletes a thin client device.</p>', 'DeleteEnvironment' => '<p>Deletes an environment.</p>', 'DeregisterDevice' => '<p>Deregisters a thin client device.</p>', 'GetDevice' => '<p>Returns information for a thin client device.</p>', 'GetEnvironment' => '<p>Returns information for an environment.</p>', 'GetSoftwareSet' => '<p>Returns information for a software set.</p>', 'ListDevices' => '<p>Returns a list of thin client devices.</p>', 'ListEnvironments' => '<p>Returns a list of environments.</p>', 'ListSoftwareSets' => '<p>Returns a list of software sets.</p>', 'ListTagsForResource' => '<p>Returns a list of tags for a resource.</p>', 'TagResource' => '<p>Assigns one or more tags (key-value pairs) to the specified resource.</p>', 'UntagResource' => '<p>Removes a tag or tags from a resource.</p>', 'UpdateDevice' => '<p>Updates a thin client device.</p>', 'UpdateEnvironment' => '<p>Updates an environment.</p>', 'UpdateSoftwareSet' => '<p>Updates a software set.</p>', ], 'shapes' => [ 'AccessDeniedException' => [ 'base' => '<p>You do not have sufficient access to perform this action.</p>', 'refs' => [], ], 'ActivationCode' => [ 'base' => NULL, 'refs' => [ 'Environment$activationCode' => '<p>The activation code to register a device to the environment.</p>', 'EnvironmentSummary$activationCode' => '<p>The activation code to register a device to the environment.</p>', ], ], 'ApplyTimeOf' => [ 'base' => NULL, 'refs' => [ 'MaintenanceWindow$applyTimeOf' => '<p>The option to set the maintenance window during the device local time or Universal Coordinated Time (UTC).</p>', ], ], 'Arn' => [ 'base' => NULL, 'refs' => [ 'CreateEnvironmentRequest$desktopArn' => '<p>The Amazon Resource Name (ARN) of the desktop to stream from Amazon WorkSpaces, WorkSpaces Web, or AppStream 2.0.</p>', 'Device$arn' => '<p>The Amazon Resource Name (ARN) of the device.</p>', 'DeviceSummary$arn' => '<p>The Amazon Resource Name (ARN) of the device.</p>', 'Environment$desktopArn' => '<p>The Amazon Resource Name (ARN) of the desktop to stream from Amazon WorkSpaces, WorkSpaces Web, or AppStream 2.0.</p>', 'Environment$arn' => '<p>The Amazon Resource Name (ARN) of the environment.</p>', 'EnvironmentSummary$desktopArn' => '<p>The Amazon Resource Name (ARN) of the desktop to stream from Amazon WorkSpaces, WorkSpaces Web, or AppStream 2.0.</p>', 'EnvironmentSummary$arn' => '<p>The Amazon Resource Name (ARN) of the environment.</p>', 'SoftwareSet$arn' => '<p>The Amazon Resource Name (ARN) of the software set.</p>', 'SoftwareSetSummary$arn' => '<p>The Amazon Resource Name (ARN) of the software set.</p>', 'UpdateEnvironmentRequest$desktopArn' => '<p>The Amazon Resource Name (ARN) of the desktop to stream from Amazon WorkSpaces, WorkSpaces Web, or AppStream 2.0.</p>', ], ], 'ClientToken' => [ 'base' => NULL, 'refs' => [ 'CreateEnvironmentRequest$clientToken' => '<p>Specifies a unique, case-sensitive identifier that you provide to ensure the idempotency of the request. This lets you safely retry the request without accidentally performing the same operation a second time. Passing the same value to a later call to an operation requires that you also pass the same value for all other parameters. We recommend that you use a <a href="https://wikipedia.org/wiki/Universally_unique_identifier">UUID type of value</a>.</p> <p>If you don\'t provide this value, then Amazon Web Services generates a random one for you.</p> <p>If you retry the operation with the same <code>ClientToken</code>, but with different parameters, the retry fails with an <code>IdempotentParameterMismatch</code> error.</p>', 'DeleteDeviceRequest$clientToken' => '<p>Specifies a unique, case-sensitive identifier that you provide to ensure the idempotency of the request. This lets you safely retry the request without accidentally performing the same operation a second time. Passing the same value to a later call to an operation requires that you also pass the same value for all other parameters. We recommend that you use a <a href="https://wikipedia.org/wiki/Universally_unique_identifier">UUID type of value</a>.</p> <p>If you don\'t provide this value, then Amazon Web Services generates a random one for you.</p> <p>If you retry the operation with the same <code>ClientToken</code>, but with different parameters, the retry fails with an <code>IdempotentParameterMismatch</code> error.</p>', 'DeleteEnvironmentRequest$clientToken' => '<p>Specifies a unique, case-sensitive identifier that you provide to ensure the idempotency of the request. This lets you safely retry the request without accidentally performing the same operation a second time. Passing the same value to a later call to an operation requires that you also pass the same value for all other parameters. We recommend that you use a <a href="https://wikipedia.org/wiki/Universally_unique_identifier">UUID type of value</a>.</p> <p>If you don\'t provide this value, then Amazon Web Services generates a random one for you.</p> <p>If you retry the operation with the same <code>ClientToken</code>, but with different parameters, the retry fails with an <code>IdempotentParameterMismatch</code> error.</p>', 'DeregisterDeviceRequest$clientToken' => '<p>Specifies a unique, case-sensitive identifier that you provide to ensure the idempotency of the request. This lets you safely retry the request without accidentally performing the same operation a second time. Passing the same value to a later call to an operation requires that you also pass the same value for all other parameters. We recommend that you use a <a href="https://wikipedia.org/wiki/Universally_unique_identifier">UUID type of value</a>.</p> <p>If you don\'t provide this value, then Amazon Web Services generates a random one for you.</p> <p>If you retry the operation with the same <code>ClientToken</code>, but with different parameters, the retry fails with an <code>IdempotentParameterMismatch</code> error.</p>', ], ], 'ConflictException' => [ 'base' => '<p>The requested operation would cause a conflict with the current state of a service resource associated with the request. Resolve the conflict before retrying this request.</p>', 'refs' => [], ], 'CreateEnvironmentRequest' => [ 'base' => NULL, 'refs' => [], ], 'CreateEnvironmentResponse' => [ 'base' => NULL, 'refs' => [], ], 'DayOfWeek' => [ 'base' => NULL, 'refs' => [ 'DayOfWeekList$member' => NULL, ], ], 'DayOfWeekList' => [ 'base' => NULL, 'refs' => [ 'MaintenanceWindow$daysOfTheWeek' => '<p>The days of the week during which the maintenance window is open.</p>', ], ], 'DeleteDeviceRequest' => [ 'base' => NULL, 'refs' => [], ], 'DeleteDeviceResponse' => [ 'base' => NULL, 'refs' => [], ], 'DeleteEnvironmentRequest' => [ 'base' => NULL, 'refs' => [], ], 'DeleteEnvironmentResponse' => [ 'base' => NULL, 'refs' => [], ], 'DeregisterDeviceRequest' => [ 'base' => NULL, 'refs' => [], ], 'DeregisterDeviceResponse' => [ 'base' => NULL, 'refs' => [], ], 'DesktopEndpoint' => [ 'base' => NULL, 'refs' => [ 'CreateEnvironmentRequest$desktopEndpoint' => '<p>The URL for the identity provider login (only for environments that use AppStream 2.0).</p>', 'Environment$desktopEndpoint' => '<p>The URL for the identity provider login (only for environments that use AppStream 2.0).</p>', 'EnvironmentSummary$desktopEndpoint' => '<p>The URL for the identity provider login (only for environments that use AppStream 2.0).</p>', 'UpdateEnvironmentRequest$desktopEndpoint' => '<p>The URL for the identity provider login (only for environments that use AppStream 2.0).</p>', ], ], 'DesktopType' => [ 'base' => NULL, 'refs' => [ 'Environment$desktopType' => '<p>The type of streaming desktop for the environment.</p>', 'EnvironmentSummary$desktopType' => '<p>The type of streaming desktop for the environment.</p>', ], ], 'Device' => [ 'base' => '<p>Describes a thin client device.</p>', 'refs' => [ 'GetDeviceResponse$device' => '<p>Describes an device.</p>', ], ], 'DeviceId' => [ 'base' => NULL, 'refs' => [ 'DeleteDeviceRequest$id' => '<p>The ID of the device to delete.</p>', 'DeregisterDeviceRequest$id' => '<p>The ID of the device to deregister.</p>', 'Device$id' => '<p>The ID of the device.</p>', 'DeviceSummary$id' => '<p>The ID of the device.</p>', 'GetDeviceRequest$id' => '<p>The ID of the device for which to return information.</p>', 'UpdateDeviceRequest$id' => '<p>The ID of the device to update.</p>', ], ], 'DeviceList' => [ 'base' => NULL, 'refs' => [ 'ListDevicesResponse$devices' => '<p>Describes devices.</p>', ], ], 'DeviceName' => [ 'base' => NULL, 'refs' => [ 'Device$name' => '<p>The name of the device.</p>', 'DeviceSummary$name' => '<p>The name of the device.</p>', 'UpdateDeviceRequest$name' => '<p>The name of the device to update.</p>', ], ], 'DeviceSoftwareSetComplianceStatus' => [ 'base' => NULL, 'refs' => [ 'Device$softwareSetComplianceStatus' => '<p>Describes if the software currently installed on the device is a supported version.</p>', ], ], 'DeviceStatus' => [ 'base' => NULL, 'refs' => [ 'Device$status' => '<p>The status of the device.</p>', 'DeviceSummary$status' => '<p>The status of the device.</p>', ], ], 'DeviceSummary' => [ 'base' => '<p>Describes a thin client device.</p>', 'refs' => [ 'DeviceList$member' => NULL, 'UpdateDeviceResponse$device' => '<p>Describes a device.</p>', ], ], 'EmbeddedTag' => [ 'base' => '<p>The resource and internal ID of a resource to tag.</p>', 'refs' => [ 'Device$tags' => '<p>The tag keys and optional values for the resource.</p>', 'DeviceSummary$tags' => '<p>The tag keys and optional values for the resource.</p>', 'Environment$tags' => '<p>The tag keys and optional values for the resource.</p>', 'EnvironmentSummary$tags' => '<p>The tag keys and optional values for the resource.</p>', ], ], 'Environment' => [ 'base' => '<p>Describes an environment.</p>', 'refs' => [ 'GetEnvironmentResponse$environment' => '<p>Describes an environment.</p>', ], ], 'EnvironmentId' => [ 'base' => NULL, 'refs' => [ 'DeleteEnvironmentRequest$id' => '<p>The ID of the environment to delete.</p>', 'Device$environmentId' => '<p>The ID of the environment the device is associated with.</p>', 'DeviceSummary$environmentId' => '<p>The ID of the environment the device is associated with.</p>', 'Environment$id' => '<p>The ID of the environment.</p>', 'EnvironmentSummary$id' => '<p>The ID of the environment.</p>', 'GetEnvironmentRequest$id' => '<p>The ID of the environment for which to return information.</p>', 'UpdateEnvironmentRequest$id' => '<p>The ID of the environment to update.</p>', ], ], 'EnvironmentList' => [ 'base' => NULL, 'refs' => [ 'ListEnvironmentsResponse$environments' => '<p>Describes environments.</p>', ], ], 'EnvironmentName' => [ 'base' => NULL, 'refs' => [ 'CreateEnvironmentRequest$name' => '<p>The name for the environment.</p>', 'Environment$name' => '<p>The name of the environment.</p>', 'EnvironmentSummary$name' => '<p>The name of the environment.</p>', 'UpdateEnvironmentRequest$name' => '<p>The name of the environment to update.</p>', ], ], 'EnvironmentSoftwareSetComplianceStatus' => [ 'base' => NULL, 'refs' => [ 'Environment$softwareSetComplianceStatus' => '<p>Describes if the software currently installed on all devices in the environment is a supported version.</p>', ], ], 'EnvironmentSummary' => [ 'base' => '<p>Describes an environment.</p>', 'refs' => [ 'CreateEnvironmentResponse$environment' => '<p>Describes an environment.</p>', 'EnvironmentList$member' => NULL, 'UpdateEnvironmentResponse$environment' => '<p>Describes an environment.</p>', ], ], 'ExceptionMessage' => [ 'base' => NULL, 'refs' => [ 'AccessDeniedException$message' => NULL, 'ConflictException$message' => NULL, 'InternalServerException$message' => NULL, 'InternalServiceException$message' => NULL, 'ResourceNotFoundException$message' => NULL, 'ServiceQuotaExceededException$message' => NULL, 'ThrottlingException$message' => NULL, 'ValidationException$message' => NULL, 'ValidationExceptionField$message' => '<p>A message that describes the reason for the exception.</p>', ], ], 'FieldName' => [ 'base' => NULL, 'refs' => [ 'ValidationExceptionField$name' => '<p>The name of the exception.</p>', ], ], 'GetDeviceRequest' => [ 'base' => NULL, 'refs' => [], ], 'GetDeviceResponse' => [ 'base' => NULL, 'refs' => [], ], 'GetEnvironmentRequest' => [ 'base' => NULL, 'refs' => [], ], 'GetEnvironmentResponse' => [ 'base' => NULL, 'refs' => [], ], 'GetSoftwareSetRequest' => [ 'base' => NULL, 'refs' => [], ], 'GetSoftwareSetResponse' => [ 'base' => NULL, 'refs' => [], ], 'Hour' => [ 'base' => NULL, 'refs' => [ 'MaintenanceWindow$startTimeHour' => '<p>The hour for the maintenance window start (<code>00</code>-<code>23</code>).</p>', 'MaintenanceWindow$endTimeHour' => '<p>The hour for the maintenance window end (<code>00</code>-<code>23</code>).</p>', ], ], 'Integer' => [ 'base' => NULL, 'refs' => [ 'Environment$registeredDevicesCount' => '<p>The number of devices registered to the environment.</p>', ], ], 'InternalServerException' => [ 'base' => '<p>The server encountered an internal error and is unable to complete the request.</p>', 'refs' => [], ], 'InternalServiceException' => [ 'base' => '<p>Request processing failed due to some unknown error, exception, or failure.</p>', 'refs' => [], ], 'KmsKeyArn' => [ 'base' => NULL, 'refs' => [ 'CreateEnvironmentRequest$kmsKeyArn' => '<p>The Amazon Resource Name (ARN) of the Key Management Service key to use to encrypt the environment.</p>', 'Device$kmsKeyArn' => '<p>The Amazon Resource Name (ARN) of the Key Management Service key used to encrypt the device.</p>', 'Environment$kmsKeyArn' => '<p>The Amazon Resource Name (ARN) of the Key Management Service key used to encrypt the environment.</p>', ], ], 'ListDevicesRequest' => [ 'base' => NULL, 'refs' => [], ], 'ListDevicesResponse' => [ 'base' => NULL, 'refs' => [], ], 'ListEnvironmentsRequest' => [ 'base' => NULL, 'refs' => [], ], 'ListEnvironmentsResponse' => [ 'base' => NULL, 'refs' => [], ], 'ListSoftwareSetsRequest' => [ 'base' => NULL, 'refs' => [], ], 'ListSoftwareSetsResponse' => [ 'base' => NULL, 'refs' => [], ], 'ListTagsForResourceRequest' => [ 'base' => NULL, 'refs' => [], ], 'ListTagsForResourceResponse' => [ 'base' => NULL, 'refs' => [], ], 'MaintenanceWindow' => [ 'base' => '<p>Describes the maintenance window for a thin client device.</p>', 'refs' => [ 'CreateEnvironmentRequest$maintenanceWindow' => '<p>A specification for a time window to apply software updates.</p>', 'Environment$maintenanceWindow' => '<p>A specification for a time window to apply software updates.</p>', 'EnvironmentSummary$maintenanceWindow' => '<p>A specification for a time window to apply software updates.</p>', 'UpdateEnvironmentRequest$maintenanceWindow' => '<p>A specification for a time window to apply software updates.</p>', ], ], 'MaintenanceWindowType' => [ 'base' => NULL, 'refs' => [ 'MaintenanceWindow$type' => '<p>An option to select the default or custom maintenance window.</p>', ], ], 'MaxResults' => [ 'base' => NULL, 'refs' => [ 'ListDevicesRequest$maxResults' => '<p>The maximum number of results that are returned per call. You can use <code>nextToken</code> to obtain further pages of results.</p> <p>This is only an upper limit. The actual number of results returned per call might be fewer than the specified maximum.</p>', 'ListEnvironmentsRequest$maxResults' => '<p>The maximum number of results that are returned per call. You can use <code>nextToken</code> to obtain further pages of results.</p> <p>This is only an upper limit. The actual number of results returned per call might be fewer than the specified maximum.</p>', 'ListSoftwareSetsRequest$maxResults' => '<p>The maximum number of results that are returned per call. You can use <code>nextToken</code> to obtain further pages of results.</p> <p>This is only an upper limit. The actual number of results returned per call might be fewer than the specified maximum.</p>', ], ], 'Minute' => [ 'base' => NULL, 'refs' => [ 'MaintenanceWindow$startTimeMinute' => '<p>The minutes past the hour for the maintenance window start (<code>00</code>-<code>59</code>).</p>', 'MaintenanceWindow$endTimeMinute' => '<p>The minutes for the maintenance window end (<code>00</code>-<code>59</code>).</p>', ], ], 'PaginationToken' => [ 'base' => NULL, 'refs' => [ 'ListDevicesRequest$nextToken' => '<p>If <code>nextToken</code> is returned, there are more results available. The value of <code>nextToken</code> is a unique pagination token for each page. Make the call again using the returned token to retrieve the next page. Keep all other arguments unchanged. Each pagination token expires after 24 hours. Using an expired pagination token will return an <i>HTTP 400 InvalidToken error</i>.</p>', 'ListDevicesResponse$nextToken' => '<p>If <code>nextToken</code> is returned, there are more results available. The value of <code>nextToken</code> is a unique pagination token for each page. Make the call again using the returned token to retrieve the next page. Keep all other arguments unchanged. Each pagination token expires after 24 hours. Using an expired pagination token will return an <i>HTTP 400 InvalidToken error</i>.</p>', 'ListEnvironmentsRequest$nextToken' => '<p>If <code>nextToken</code> is returned, there are more results available. The value of <code>nextToken</code> is a unique pagination token for each page. Make the call again using the returned token to retrieve the next page. Keep all other arguments unchanged. Each pagination token expires after 24 hours. Using an expired pagination token will return an <i>HTTP 400 InvalidToken error</i>.</p>', 'ListEnvironmentsResponse$nextToken' => '<p>If <code>nextToken</code> is returned, there are more results available. The value of <code>nextToken</code> is a unique pagination token for each page. Make the call again using the returned token to retrieve the next page. Keep all other arguments unchanged. Each pagination token expires after 24 hours. Using an expired pagination token will return an <i>HTTP 400 InvalidToken error</i>.</p>', 'ListSoftwareSetsRequest$nextToken' => '<p>If <code>nextToken</code> is returned, there are more results available. The value of <code>nextToken</code> is a unique pagination token for each page. Make the call again using the returned token to retrieve the next page. Keep all other arguments unchanged. Each pagination token expires after 24 hours. Using an expired pagination token will return an <i>HTTP 400 InvalidToken error</i>.</p>', 'ListSoftwareSetsResponse$nextToken' => '<p>If <code>nextToken</code> is returned, there are more results available. The value of <code>nextToken</code> is a unique pagination token for each page. Make the call again using the returned token to retrieve the next page. Keep all other arguments unchanged. Each pagination token expires after 24 hours. Using an expired pagination token will return an <i>HTTP 400 InvalidToken error</i>.</p>', ], ], 'QuotaCode' => [ 'base' => NULL, 'refs' => [ 'ServiceQuotaExceededException$quotaCode' => '<p>The code for the quota in <a href="https://docs.aws.amazon.com/servicequotas/latest/userguide/intro.html">Service Quotas</a>.</p>', 'ThrottlingException$quotaCode' => '<p>The code for the quota in <a href="https://docs.aws.amazon.com/servicequotas/latest/userguide/intro.html">Service Quotas</a>.</p>', ], ], 'ResourceId' => [ 'base' => NULL, 'refs' => [ 'ConflictException$resourceId' => '<p>The ID of the resource associated with the request.</p>', 'ResourceNotFoundException$resourceId' => '<p>The ID of the resource associated with the request.</p>', 'ServiceQuotaExceededException$resourceId' => '<p>The ID of the resource that exceeds the service quota.</p>', ], ], 'ResourceNotFoundException' => [ 'base' => '<p>The resource specified in the request was not found.</p>', 'refs' => [], ], 'ResourceType' => [ 'base' => NULL, 'refs' => [ 'ConflictException$resourceType' => '<p>The type of the resource associated with the request.</p>', 'ResourceNotFoundException$resourceType' => '<p>The type of the resource associated with the request.</p>', 'ServiceQuotaExceededException$resourceType' => '<p>The type of the resource that exceeds the service quota.</p>', ], ], 'RetryAfterSeconds' => [ 'base' => NULL, 'refs' => [ 'InternalServerException$retryAfterSeconds' => '<p>The number of seconds to wait before retrying the next request.</p>', 'InternalServiceException$retryAfterSeconds' => '<p>The number of seconds to wait before retrying the next request.</p>', 'ThrottlingException$retryAfterSeconds' => '<p>The number of seconds to wait before retrying the next request.</p>', ], ], 'ServiceCode' => [ 'base' => NULL, 'refs' => [ 'ServiceQuotaExceededException$serviceCode' => '<p>The code for the service in <a href="https://docs.aws.amazon.com/servicequotas/latest/userguide/intro.html">Service Quotas</a>.</p>', 'ThrottlingException$serviceCode' => '<p>The code for the service in <a href="https://docs.aws.amazon.com/servicequotas/latest/userguide/intro.html">Service Quotas</a>.</p>', ], ], 'ServiceQuotaExceededException' => [ 'base' => '<p>Your request exceeds a service quota.</p>', 'refs' => [], ], 'Software' => [ 'base' => '<p>Describes software.</p>', 'refs' => [ 'SoftwareList$member' => NULL, ], ], 'SoftwareList' => [ 'base' => NULL, 'refs' => [ 'SoftwareSet$software' => '<p>A list of the software components in the software set.</p>', ], ], 'SoftwareSet' => [ 'base' => '<p>Describes a software set.</p>', 'refs' => [ 'GetSoftwareSetResponse$softwareSet' => '<p>Describes a software set.</p>', ], ], 'SoftwareSetId' => [ 'base' => NULL, 'refs' => [ 'CreateEnvironmentRequest$desiredSoftwareSetId' => '<p>The ID of the software set to apply.</p>', 'Device$currentSoftwareSetId' => '<p>The ID of the software set currently installed on the device.</p>', 'Device$desiredSoftwareSetId' => '<p>The ID of the software set which the device has been set to.</p>', 'Device$pendingSoftwareSetId' => '<p>The ID of the software set that is pending to be installed on the device.</p>', 'DeviceSummary$currentSoftwareSetId' => '<p>The ID of the software set currently installed on the device.</p>', 'DeviceSummary$desiredSoftwareSetId' => '<p>The ID of the software set which the device has been set to.</p>', 'DeviceSummary$pendingSoftwareSetId' => '<p>The ID of the software set that is pending to be installed on the device.</p>', 'Environment$desiredSoftwareSetId' => '<p>The ID of the software set to apply.</p>', 'Environment$pendingSoftwareSetId' => '<p>The ID of the software set that is pending to be installed.</p>', 'EnvironmentSummary$desiredSoftwareSetId' => '<p>The ID of the software set to apply.</p>', 'EnvironmentSummary$pendingSoftwareSetId' => '<p>The ID of the software set that is pending to be installed.</p>', 'GetSoftwareSetRequest$id' => '<p>The ID of the software set for which to return information.</p>', 'SoftwareSet$id' => '<p>The ID of the software set.</p>', 'SoftwareSetSummary$id' => '<p>The ID of the software set.</p>', 'UpdateDeviceRequest$desiredSoftwareSetId' => '<p>The ID of the software set to apply.</p>', 'UpdateSoftwareSetRequest$id' => '<p>The ID of the software set to update.</p>', ], ], 'SoftwareSetIdOrEmptyString' => [ 'base' => NULL, 'refs' => [ 'UpdateEnvironmentRequest$desiredSoftwareSetId' => '<p>The ID of the software set to apply.</p>', ], ], 'SoftwareSetList' => [ 'base' => NULL, 'refs' => [ 'ListSoftwareSetsResponse$softwareSets' => '<p>Describes software sets.</p>', ], ], 'SoftwareSetSummary' => [ 'base' => '<p>Describes a software set.</p>', 'refs' => [ 'SoftwareSetList$member' => NULL, ], ], 'SoftwareSetUpdateMode' => [ 'base' => NULL, 'refs' => [ 'CreateEnvironmentRequest$softwareSetUpdateMode' => '<p>An option to define which software updates to apply.</p>', 'Environment$softwareSetUpdateMode' => '<p>An option to define which software updates to apply.</p>', 'EnvironmentSummary$softwareSetUpdateMode' => '<p>An option to define which software updates to apply.</p>', 'UpdateEnvironmentRequest$softwareSetUpdateMode' => '<p>An option to define which software updates to apply.</p>', ], ], 'SoftwareSetUpdateSchedule' => [ 'base' => NULL, 'refs' => [ 'CreateEnvironmentRequest$softwareSetUpdateSchedule' => '<p>An option to define if software updates should be applied within a maintenance window.</p>', 'Device$softwareSetUpdateSchedule' => '<p>An option to define if software updates should be applied within a maintenance window.</p>', 'DeviceSummary$softwareSetUpdateSchedule' => '<p>An option to define if software updates should be applied within a maintenance window.</p>', 'Environment$softwareSetUpdateSchedule' => '<p>An option to define if software updates should be applied within a maintenance window.</p>', 'EnvironmentSummary$softwareSetUpdateSchedule' => '<p>An option to define if software updates should be applied within a maintenance window.</p>', 'UpdateDeviceRequest$softwareSetUpdateSchedule' => '<p>An option to define if software updates should be applied within a maintenance window.</p>', 'UpdateEnvironmentRequest$softwareSetUpdateSchedule' => '<p>An option to define if software updates should be applied within a maintenance window.</p>', ], ], 'SoftwareSetUpdateStatus' => [ 'base' => NULL, 'refs' => [ 'Device$softwareSetUpdateStatus' => '<p>Describes if the device has a supported version of software installed.</p>', ], ], 'SoftwareSetValidationStatus' => [ 'base' => NULL, 'refs' => [ 'SoftwareSet$validationStatus' => '<p>An option to define if the software set has been validated.</p>', 'SoftwareSetSummary$validationStatus' => '<p>An option to define if the software set has been validated.</p>', 'UpdateSoftwareSetRequest$validationStatus' => '<p>An option to define if the software set has been validated.</p>', ], ], 'String' => [ 'base' => NULL, 'refs' => [ 'Device$serialNumber' => '<p>The hardware serial number of the device.</p>', 'Device$model' => '<p>The model number of the device.</p>', 'Device$currentSoftwareSetVersion' => '<p>The version of the software set currently installed on the device.</p>', 'Device$pendingSoftwareSetVersion' => '<p>The version of the software set that is pending to be installed on the device.</p>', 'DeviceSummary$serialNumber' => '<p>The hardware serial number of the device.</p>', 'DeviceSummary$model' => '<p>The model number of the device.</p>', 'EmbeddedTag$resourceArn' => '<p>The Amazon Resource Name (ARN) of a resource to tag.</p>', 'EmbeddedTag$internalId' => '<p>The internal ID of a resource to tag.</p>', 'Environment$pendingSoftwareSetVersion' => '<p>The version of the software set that is pending to be installed.</p>', 'ListTagsForResourceRequest$resourceArn' => '<p>The Amazon Resource Name (ARN) of the resource for which you want to retrieve tags.</p>', 'Software$name' => '<p>The name of the software component.</p>', 'Software$version' => '<p>The version of the software component.</p>', 'SoftwareSet$version' => '<p>The version of the software set.</p>', 'SoftwareSetSummary$version' => '<p>The version of the software set.</p>', 'TagKeys$member' => NULL, 'TagResourceRequest$resourceArn' => '<p>The Amazon Resource Name (ARN) of the resource that you want to tag.</p>', 'TagsMap$key' => NULL, 'TagsMap$value' => NULL, 'UntagResourceRequest$resourceArn' => '<p>The Amazon Resource Name (ARN) of the resource that you want to untag.</p>', ], ], 'TagKeys' => [ 'base' => NULL, 'refs' => [ 'UntagResourceRequest$tagKeys' => '<p>The keys of the key-value pairs for the tag or tags you want to remove from the specified resource.</p>', ], ], 'TagResourceRequest' => [ 'base' => NULL, 'refs' => [], ], 'TagResourceResponse' => [ 'base' => NULL, 'refs' => [], ], 'TagsMap' => [ 'base' => NULL, 'refs' => [ 'CreateEnvironmentRequest$tags' => '<p>A map of the key-value pairs of the tag or tags to assign to the resource.</p>', 'ListTagsForResourceResponse$tags' => '<p>A map of the key-value pairs for the tag or tags assigned to the specified resource.</p>', 'TagResourceRequest$tags' => '<p>A map of the key-value pairs of the tag or tags to assign to the resource.</p>', ], ], 'TargetDeviceStatus' => [ 'base' => NULL, 'refs' => [ 'DeregisterDeviceRequest$targetDeviceStatus' => '<p>The desired new status for the device.</p>', ], ], 'ThrottlingException' => [ 'base' => '<p>The request was denied due to request throttling.</p>', 'refs' => [], ], 'Timestamp' => [ 'base' => NULL, 'refs' => [ 'Device$lastConnectedAt' => '<p>The timestamp of the most recent session on the device.</p>', 'Device$lastPostureAt' => '<p>The timestamp of the most recent check-in of the device.</p>', 'Device$createdAt' => '<p>The timestamp of when the device was created.</p>', 'Device$updatedAt' => '<p>The timestamp of when the device was updated.</p>', 'DeviceSummary$lastConnectedAt' => '<p>The timestamp of the most recent session on the device.</p>', 'DeviceSummary$lastPostureAt' => '<p>The timestamp of the most recent check-in of the device.</p>', 'DeviceSummary$createdAt' => '<p>The timestamp of when the device was created.</p>', 'DeviceSummary$updatedAt' => '<p>The timestamp of when the device was updated.</p>', 'Environment$createdAt' => '<p>The timestamp of when the environment was created.</p>', 'Environment$updatedAt' => '<p>The timestamp of when the device was updated.</p>', 'EnvironmentSummary$createdAt' => '<p>The timestamp of when the environment was created.</p>', 'EnvironmentSummary$updatedAt' => '<p>The timestamp of when the device was updated.</p>', 'SoftwareSet$releasedAt' => '<p>The timestamp of when the software set was released.</p>', 'SoftwareSet$supportedUntil' => '<p>The timestamp of the end of support for the software set.</p>', 'SoftwareSetSummary$releasedAt' => '<p>The timestamp of when the software set was released.</p>', 'SoftwareSetSummary$supportedUntil' => '<p>The timestamp of the end of support for the software set.</p>', ], ], 'UntagResourceRequest' => [ 'base' => NULL, 'refs' => [], ], 'UntagResourceResponse' => [ 'base' => NULL, 'refs' => [], ], 'UpdateDeviceRequest' => [ 'base' => NULL, 'refs' => [], ], 'UpdateDeviceResponse' => [ 'base' => NULL, 'refs' => [], ], 'UpdateEnvironmentRequest' => [ 'base' => NULL, 'refs' => [], ], 'UpdateEnvironmentResponse' => [ 'base' => NULL, 'refs' => [], ], 'UpdateSoftwareSetRequest' => [ 'base' => NULL, 'refs' => [], ], 'UpdateSoftwareSetResponse' => [ 'base' => NULL, 'refs' => [], ], 'ValidationException' => [ 'base' => '<p>The input fails to satisfy the specified constraints.</p>', 'refs' => [], ], 'ValidationExceptionField' => [ 'base' => '<p>Describes a validation exception.</p>', 'refs' => [ 'ValidationExceptionFieldList$member' => NULL, ], ], 'ValidationExceptionFieldList' => [ 'base' => NULL, 'refs' => [ 'ValidationException$fieldList' => '<p>A list of fields that didn\'t validate.</p>', ], ], 'ValidationExceptionReason' => [ 'base' => NULL, 'refs' => [ 'ValidationException$reason' => '<p>The reason for the exception.</p>', ], ], ],];
