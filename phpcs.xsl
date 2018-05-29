<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet exclude-result-prefixes="xs html" version="2.0" xmlns:file="http://expath.org/ns/file" xmlns:html="http://www.w3.org/1999/xhtml" xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:template match="phpcs">
    <html>
      <head>
        <title>PHPCS Report</title>
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" />
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" rel="stylesheet" />
        <script src="https://code.jquery.com/jquery-3.3.1.min.js"/>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" />
        <style>
          .fixable{
            color: grey;
          }
          .fixable th {
            color:grey;
          }
          
          .nowarnings .fixable {
            display: none;
          }
        </style>
        <script>
          $(document).ready(function(){
            $('#toggle').click(function(e){
              e.preventDefault();
              $('body').toggleClass('nowarnings');
            });
          });
        </script>
      </head>
      <body class="nowarnings">
        <div class="container">
          <div class="row">
            <div class="col-md-5">
              <h3>Overview</h3>
              <ul>
                <xsl:apply-templates mode="summary" />
              </ul>
            </div>
            <div class="col-md-7">
              <h3>Details</h3>
              <a id="toggle">Toggle warnings</a>
              <xsl:apply-templates mode="detail" />
            </div>
          </div>
        </div>
      </body>
    </html>
  </xsl:template>

  <xsl:template match="file" mode="summary">
    <xsl:variable name="count" select="count(./error[@fixable != 1])" />
    <xsl:if test="$count gt 0">
      <li>
        <a href="#{generate-id(.)}"> <xsl:value-of select="replace(@name, '.*/', '')" /> - <xsl:value-of select="count(./error[@fixable != 1])" /> </a>
      </li>
    </xsl:if>
  </xsl:template>

  <xsl:template match="file" mode="detail">
    <xsl:variable name="errors" select="count(./error[@fixable != 1])" />
    <xsl:variable name="warnings" select="count(./error[@fixable = 1])" />
    <xsl:variable name="class">
      <xsl:if test="$errors = 0">fixable</xsl:if>
    </xsl:variable>
    <div class="{$class}">
      <p id="{generate-id(.)}"> <xsl:value-of select="@name" /><br />
        Errors: <xsl:value-of select="$errors" /> | 
        Warnings: <xsl:value-of select="$warnings" /> 
      </p>
      <table class="table table-stripped table-compressed">
        <thead>
          <tr>
            <th width="100px">Line</th>
            <th>Message</th>
          </tr>
        </thead>
        <xsl:apply-templates mode="detail" select="error" />
      </table>
    </div>
  </xsl:template>

  <xsl:template match="error" mode="detail">
    <xsl:variable name="class">
      <xsl:choose>
        <xsl:when test="@fixable = 1">fixable</xsl:when>
        <xsl:otherwise />
      </xsl:choose>
    </xsl:variable>

    <tr class="{$class}">
      <td> <xsl:value-of select="@line" />:<xsl:value-of select="@column" /> </td>
      <td>
        <xsl:apply-templates mode="detail" />
        <br />
        <xsl:value-of select="@source" />
      </td>
    </tr>
  </xsl:template>

  <xsl:template match="@* | node()">
    <xsl:copy>
      <xsl:apply-templates select="@* | node()" />
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>
