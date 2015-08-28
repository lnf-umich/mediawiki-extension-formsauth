# mediawiki-extension-formsauth
This MediaWiki extension allows the use of the standard ASP.NET FormsAuthentication cookie for wiki log ins. It also can redirect the standard wiki login form to a different URL for logging in.

## Requirements
In order to read the cookie, the wiki web server must be in the same domain as the FormsAuthentication cookie. The visibilty of the cookie is determined by the `domain` attribute in the web.config `forms` element. For example:
```xml
<configuration>
   <system.web>
     <authentication mode="Forms">
        <forms name="myAuthCookie" loginUrl="/login.aspx" domain="mydomain.com"></forms>
     </authentication>
   </system.web>
</configuration>
```
In this case any server in mydomain.com will be able to read the cookie. In addion, there must be a http endpoint available that is capable of decoding the cookie value (i.e. the same application that provides the log in page) and returns a properly formatted json response (see below).

## Extension configuration
Add the extension in LocalSettings.php with:
```php
require_once("$IP/extensions/FormsAuth/FormsAuth.php");
```
Then set the following configuration array variable (with example values):
```php
$wgFormsAuthConfig = array(
  //the name of the FormsAuthentication cookie, note: if the name contains a
  //dot ('.') then this will be replaced by an underscore ('_') in PHP
  'cookie'    => "myAuthCookie",
  //the url of the endpoint that checks (decodes) the cookie and returns a properly
  //formatted json response
  'checkUrl'  => "https://www.mydomain.com/authcheck.ashx",
  //the url of the ASP.NET log in page that generates the cookie
  'loginUrl'  => "https://www.mydomain.com/login.aspx",
  //when true the standard wiki log in page will redirect to loginUrl
  'redirect'  => true,
  //when true a log file (forms-auth.log) will be kept in the FormsAuth extension
  //directory, note: the web service must have write permission on this directory
  //(warning: A LOT of messages are generated)
  'log'       => false
);
```

## Authentication Check Response
A http endpoint, specified by `checkUrl` above, must be provided that can check the value of the FormsAuthentication cookie and return the following json response (with example values):
```json
{
  "success": true,
  "message": "any message from the server, for example an error message",
  "authenticated": true,
  "username": "jsmith",
  "roles": ["admin","user","dev","etc"],
  "lastName": "Smith",
  "firstName": "John",
  "email": "jsmith@mydomain.com"
}
```
If `authenticated` is true then the extension will try to get a user from the wiki db using `username`. If one is not found a new user will be created and the user will be added to any wiki groups that match an element in `roles`.

The endpoint can be in a different ASP.NET web application than the FormsAuthentication log in page if it has matching `forms` and `machineKey` elements in its web.config file.

## Sample Authentication Check Code (C#)
This example assues there is some existing mechanism for data access (such as NHibernate) and a why to serialize anonymous objects to json (such as Newtonsoft.Json).
```csharp
//authcheck.ashx
namespace Sample
{
  public class AuthCheck : IHttpHandler
  {
    public void ProcessRequest(HttpContext context)
    {
      bool success = false; //arbitrary value, only used for logging
      string message = string.Empty;  //arbitrary value, only used for logging
      bool authenticated = false;
      string username = string.Empty;
      string[] roles = null;
      string lastName = string.Empty;
      string firstName = string.Empty;
      string email = string.Empty;
      
      if (context.Request.LogonUserIdentity.IsAuthenticated)
      {
        var user = UserRepository.GetByUsername(context.Request.LogonUserIdentity.Name);
        if (user != null)
        {
          success = true;
          message = "ok";
          authenticated = true;
          username = user.Username;
          roles = user.GetRolesStringArray();
          lastName = user.LastName;
          firstName = user.FirstName;
          email = user.Email;
        }
        else
        {
          success = false;
          message = "ERROR: authenticated user not found in database";
          authenticated = false;
          username = string.Empty;
          roles = null;
          lastName = string.Empty;
          firstName = string.Empty;
          email = string.Empty;
        }
      }
      else
      {
        success = true;
        message = "not authenticated";
        authenticated = false;
        username = string.Empty;
        roles = null;
        lastName = string.Empty;
        firstName = string.Empty;
        email = string.Empty;
      }
      
      context.Response.ContentType = "application/json";
      
      context.Response.Write(JsonConvert.SerializeObject(new
      {
        success,
        message,
        authenticated,
        username,
        roles,
        lastName,
        firstName,
        email
      }));
    }
    
    public bool IsReusable
    {
      get { return false; }
    }
  }
}
```

## References
* Web.config forms element: https://msdn.microsoft.com/en-us/library/1d3t3c61%28v=vs.85%29.aspx
* FormsAuthentication overview: https://msdn.microsoft.com/en-us/library/7t6b43z4.aspx
* MediaWiki extensions: https://www.mediawiki.org/wiki/Manual:Extensions
