<?php
namespace Sterc\MediaManager\Vendor\Aws\Signin;

use Sterc\MediaManager\Vendor\Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Sign-In Service** service.
 * @method \Sterc\MediaManager\Vendor\Aws\Result createOAuth2Token(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\Aws\Result createOAuth2Token(array{
 *     tokenInput?: array{
 *         clientId?: string,
 *         grantType?: string,
 *         code?: string,
 *         redirectUri?: string,
 *         codeVerifier?: string,
 *         refreshToken?: string,
 *         ...,
 *     },
 *     ...,
 * } $args = [])
 * @method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise createOAuth2TokenAsync(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise createOAuth2TokenAsync(array{
 *     tokenInput?: array{
 *         clientId?: string,
 *         grantType?: string,
 *         code?: string,
 *         redirectUri?: string,
 *         codeVerifier?: string,
 *         refreshToken?: string,
 *         ...,
 *     },
 *     ...,
 * } $args = [])
 * @method \Sterc\MediaManager\Vendor\Aws\Result createOAuth2TokenWithIAM(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\Aws\Result createOAuth2TokenWithIAM(array{grantType?: string, resource?: string, ...} $args = [])
 * @method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise createOAuth2TokenWithIAMAsync(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise createOAuth2TokenWithIAMAsync(array{grantType?: string, resource?: string, ...} $args = [])
 * @method \Sterc\MediaManager\Vendor\Aws\Result deleteConsoleAuthorizationConfiguration(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\Aws\Result deleteConsoleAuthorizationConfiguration(array{targetId?: string, ...} $args = [])
 * @method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise deleteConsoleAuthorizationConfigurationAsync(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise deleteConsoleAuthorizationConfigurationAsync(array{targetId?: string, ...} $args = [])
 * @method \Sterc\MediaManager\Vendor\Aws\Result deleteResourcePermissionStatement(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\Aws\Result deleteResourcePermissionStatement(array{statementId?: string, clientToken?: string, ...} $args = [])
 * @method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise deleteResourcePermissionStatementAsync(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise deleteResourcePermissionStatementAsync(array{statementId?: string, clientToken?: string, ...} $args = [])
 * @method \Sterc\MediaManager\Vendor\Aws\Result getConsoleAuthorizationConfiguration(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\Aws\Result getConsoleAuthorizationConfiguration(array{targetId?: string, ...} $args = [])
 * @method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise getConsoleAuthorizationConfigurationAsync(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise getConsoleAuthorizationConfigurationAsync(array{targetId?: string, ...} $args = [])
 * @method \Sterc\MediaManager\Vendor\Aws\Result getResourcePolicy(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\Aws\Result getResourcePolicy(array{...} $args = [])
 * @method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise getResourcePolicyAsync(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise getResourcePolicyAsync(array{...} $args = [])
 * @method \Sterc\MediaManager\Vendor\Aws\Result introspectOAuth2TokenWithIAM(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\Aws\Result introspectOAuth2TokenWithIAM(array{token?: string, tokenTypeHint?: string, ...} $args = [])
 * @method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise introspectOAuth2TokenWithIAMAsync(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise introspectOAuth2TokenWithIAMAsync(array{token?: string, tokenTypeHint?: string, ...} $args = [])
 * @method \Sterc\MediaManager\Vendor\Aws\Result listResourcePermissionStatements(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\Aws\Result listResourcePermissionStatements(array{maxResults?: int, nextToken?: string, ...} $args = [])
 * @method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise listResourcePermissionStatementsAsync(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise listResourcePermissionStatementsAsync(array{maxResults?: int, nextToken?: string, ...} $args = [])
 * @method \Sterc\MediaManager\Vendor\Aws\Result putConsoleAuthorizationConfiguration(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\Aws\Result putConsoleAuthorizationConfiguration(array{targetId?: string, ...} $args = [])
 * @method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise putConsoleAuthorizationConfigurationAsync(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise putConsoleAuthorizationConfigurationAsync(array{targetId?: string, ...} $args = [])
 * @method \Sterc\MediaManager\Vendor\Aws\Result putResourcePermissionStatement(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\Aws\Result putResourcePermissionStatement(array{
 *     sourceVpc?: string,
 *     signinSourceVpce?: string,
 *     consoleSourceVpce?: string,
 *     vpcSourceIp?: string,
 *     sourceIp?: string,
 *     requestedRegion?: string,
 *     excludedPrincipal?: string,
 *     clientToken?: string,
 *     ...,
 * } $args = [])
 * @method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise putResourcePermissionStatementAsync(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise putResourcePermissionStatementAsync(array{
 *     sourceVpc?: string,
 *     signinSourceVpce?: string,
 *     consoleSourceVpce?: string,
 *     vpcSourceIp?: string,
 *     sourceIp?: string,
 *     requestedRegion?: string,
 *     excludedPrincipal?: string,
 *     clientToken?: string,
 *     ...,
 * } $args = [])
 * @method \Sterc\MediaManager\Vendor\Aws\Result revokeOAuth2TokenWithIAM(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\Aws\Result revokeOAuth2TokenWithIAM(array{token?: string, ...} $args = [])
 * @method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise revokeOAuth2TokenWithIAMAsync(array $args = [])
 * @phpstan-method \Sterc\MediaManager\Vendor\GuzzleHttp\Promise\Promise revokeOAuth2TokenWithIAMAsync(array{token?: string, ...} $args = [])
 */
class SigninClient extends AwsClient {}
