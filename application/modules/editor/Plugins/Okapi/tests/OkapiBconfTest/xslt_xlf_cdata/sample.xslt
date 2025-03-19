<xsl:stylesheet version="1.0" 
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xliff="urn:oasis:names:tc:xliff:document:1.2">
  
  <!-- Identity transform: copies everything by default -->
  <xsl:template match="@*|node()">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>

  <!-- Template to match target elements with state="new" and remove their content -->
  <xsl:template match="xliff:target[@state='new']|xliff:target[@state='needs-review-translation']">
    <xsl:copy>
      <xsl:apply-templates select="@*"/>
      <!-- Removing the text node inside target element -->
    </xsl:copy>
  </xsl:template>

</xsl:stylesheet>
