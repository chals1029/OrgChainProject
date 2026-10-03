<?php

/**
 * Script to generate the official "Estimated Deployment and Infrastructure Costing for OrgChain" .docx document.
 * Pure Black & White / Monochrome styling, no OCR microservice references.
 * Full modern Word XML package (Word 2013-365 native, no Compatibility Mode).
 */

$outputPath = __DIR__ . '/../Estimated_Deployment_Costing_OrgChain.docx';
$publicPath = __DIR__ . '/../public/templates/Estimated_Deployment_Costing_OrgChain.docx';

// Build the WordprocessingML document content
$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <w:body>

    <!-- Document Header / Title -->
    <w:p>
      <w:pPr>
        <w:jc w:val="center"/>
        <w:spacing w:before="240" w:after="120"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:rFonts w:ascii="Arial" w:hAnsi="Arial"/>
          <w:b/>
          <w:sz w:val="34"/>
          <w:szCs w:val="34"/>
          <w:color w:val="auto"/>
        </w:rPr>
        <w:t>ESTIMATED DEPLOYMENT AND INFRASTRUCTURE COSTING</w:t>
      </w:r>
    </w:p>

    <w:p>
      <w:pPr>
        <w:jc w:val="center"/>
        <w:spacing w:before="0" w:after="240"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:rFonts w:ascii="Arial" w:hAnsi="Arial"/>
          <w:b/>
          <w:sz w:val="24"/>
          <w:szCs w:val="24"/>
          <w:color w:val="auto"/>
        </w:rPr>
        <w:t>OrgChain: Blockchain-Backed Student Organization &amp; Financial Governance Platform</w:t>
      </w:r>
    </w:p>

    <!-- Institution Subtitle -->
    <w:p>
      <w:pPr>
        <w:jc w:val="center"/>
        <w:spacing w:before="0" w:after="360"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:rFonts w:ascii="Arial" w:hAnsi="Arial"/>
          <w:i/>
          <w:sz w:val="20"/>
          <w:szCs w:val="20"/>
          <w:color w:val="auto"/>
        </w:rPr>
        <w:t>Batangas State University - The National Engineering University · ARASOF-Nasugbu Campus</w:t>
      </w:r>
    </w:p>

    <w:p><w:pPr><w:spacing w:after="240"/></w:pPr></w:p>

    <!-- Table Caption -->
    <w:p>
      <w:pPr>
        <w:spacing w:before="240" w:after="140"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:rFonts w:ascii="Arial" w:hAnsi="Arial"/>
          <w:b/>
          <w:sz w:val="22"/>
          <w:szCs w:val="22"/>
          <w:color w:val="auto"/>
        </w:rPr>
        <w:t>Table II. Estimated Deployment and Infrastructure Costing for OrgChain</w:t>
      </w:r>
    </w:p>

    <!-- Table II (Clean Academic Monochrome, No fills, Auto colors) -->
    <w:tbl>
      <w:tblPr>
        <w:tblW w:w="9800" w:type="dxa"/>
        <w:jc w:val="center"/>
        <w:tblBorders>
          <w:top w:val="single" w:sz="12" w:space="0" w:color="auto"/>
          <w:left w:val="single" w:sz="6" w:space="0" w:color="auto"/>
          <w:bottom w:val="single" w:sz="12" w:space="0" w:color="auto"/>
          <w:right w:val="single" w:sz="6" w:space="0" w:color="auto"/>
          <w:insideH w:val="single" w:sz="6" w:space="0" w:color="auto"/>
          <w:insideV w:val="single" w:sz="6" w:space="0" w:color="auto"/>
        </w:tblBorders>
      </w:tblPr>

      <!-- Header Row -->
      <w:tr>
        <w:trPr>
          <w:tblHeader/>
          <w:trHeight w:val="440"/>
        </w:trPr>
        
        <!-- Col 1 -->
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="2500" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p>
            <w:r>
              <w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:color w:val="auto"/><w:sz w:val="20"/></w:rPr>
              <w:t>Deployment Item</w:t>
            </w:r>
          </w:p>
        </w:tc>

        <!-- Col 2 -->
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="4300" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p>
            <w:r>
              <w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:color w:val="auto"/><w:sz w:val="20"/></w:rPr>
              <w:t>Technical Specifications &amp; Allocation</w:t>
            </w:r>
          </w:p>
        </w:tc>

        <!-- Col 3 -->
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="1500" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p>
            <w:r>
              <w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:color w:val="auto"/><w:sz w:val="20"/></w:rPr>
              <w:t>Term / Period</w:t>
            </w:r>
          </w:p>
        </w:tc>

        <!-- Col 4 -->
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="1500" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p>
            <w:jc w:val="right"/>
            <w:r>
              <w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:color w:val="auto"/><w:sz w:val="20"/></w:rPr>
              <w:t>Estimated Cost (PHP)</w:t>
            </w:r>
          </w:p>
        </w:tc>
      </w:tr>

      <!-- Row 1: VPS -->
      <w:tr>
        <w:trPr><w:trHeight w:val="750"/></w:trPr>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="2500" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p>
            <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:color w:val="auto"/><w:sz w:val="20"/></w:rPr><w:t>Cloud Virtual Private Server (Hostinger KVM VPS Plan)</w:t></w:r>
          </w:p>
          <w:p>
            <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:i/><w:sz w:val="18"/><w:color w:val="auto"/></w:rPr><w:t>Dedicated Virtualized Environment</w:t></w:r>
          </w:p>
        </w:tc>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="4300" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="19"/><w:color w:val="auto"/></w:rPr><w:t>• OS: Ubuntu 22.04 LTS (64-bit)</w:t></w:r></w:p>
          <w:p><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="19"/><w:color w:val="auto"/></w:rPr><w:t>• Compute: 2 to 4 vCPU Cores, 8 GB RAM, 100 GB NVMe SSD</w:t></w:r></w:p>
          <w:p><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="19"/><w:color w:val="auto"/></w:rPr><w:t>• Container Engine: Docker &amp; Docker Compose</w:t></w:r></w:p>
          <w:p><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="19"/><w:color w:val="auto"/></w:rPr><w:t>• Web / Database: Nginx, PHP 8.2+, MySQL 8.0 Community</w:t></w:r></w:p>
          <w:p><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="19"/><w:color w:val="auto"/></w:rPr><w:t>• Blockchain: Hyperledger Besu Private QBFT Consortium Nodes</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="1500" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="19"/><w:color w:val="auto"/></w:rPr><w:t>1 Year (Jan – Dec 2027)</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="1500" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p>
            <w:jc w:val="right"/>
            <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="20"/><w:color w:val="auto"/></w:rPr><w:t>₱7,200.00</w:t></w:r>
          </w:p>
          <w:p>
            <w:jc w:val="right"/>
            <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="17"/><w:color w:val="auto"/></w:rPr><w:t>(~₱600/mo)</w:t></w:r>
          </w:p>
        </w:tc>
      </w:tr>

      <!-- Row 2: Domain -->
      <w:tr>
        <w:trPr><w:trHeight w:val="550"/></w:trPr>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="2500" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p>
            <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:color w:val="auto"/><w:sz w:val="20"/></w:rPr><w:t>Domain Name Registration</w:t></w:r>
          </w:p>
        </w:tc>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="4300" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="19"/><w:color w:val="auto"/></w:rPr><w:t>Registration of a dedicated top-level domain (.com / .org) or institutional subdomain routing (DNS Management &amp; WHOIS Privacy included).</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="1500" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="19"/><w:color w:val="auto"/></w:rPr><w:t>1 Year (Jan – Dec 2027)</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="1500" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p>
            <w:jc w:val="right"/>
            <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="20"/><w:color w:val="auto"/></w:rPr><w:t>₱800.00</w:t></w:r>
          </w:p>
        </w:tc>
      </w:tr>

      <!-- Row 3: SSL -->
      <w:tr>
        <w:trPr><w:trHeight w:val="500"/></w:trPr>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="2500" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p>
            <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:color w:val="auto"/><w:sz w:val="20"/></w:rPr><w:t>SSL / TLS Security Certificate</w:t></w:r>
          </w:p>
        </w:tc>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="4300" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="19"/><w:color w:val="auto"/></w:rPr><w:t>Automated 256-bit TLS/SSL certificate encryption via Let\'s Encrypt / Certbot protocol across all portals and endpoints.</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="1500" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="19"/><w:color w:val="auto"/></w:rPr><w:t>Auto-renewing</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="1500" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p>
            <w:jc w:val="right"/>
            <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="20"/><w:color w:val="auto"/></w:rPr><w:t>FREE</w:t></w:r>
          </w:p>
          <w:p>
            <w:jc w:val="right"/>
            <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="17"/><w:color w:val="auto"/></w:rPr><w:t>(Open Source)</w:t></w:r>
          </w:p>
        </w:tc>
      </w:tr>

      <!-- Row 4: Hyperledger Besu -->
      <w:tr>
        <w:trPr><w:trHeight w:val="550"/></w:trPr>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="2500" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p>
            <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:color w:val="auto"/><w:sz w:val="20"/></w:rPr><w:t>Hyperledger Besu Enterprise Blockchain</w:t></w:r>
          </w:p>
        </w:tc>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="4300" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="19"/><w:color w:val="auto"/></w:rPr><w:t>Enterprise Ethereum permissioned consortium client (Linux Foundation). Zero gas fees (gas_price = 0), unlimited voting &amp; expense audit ledger transactions.</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="1500" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="19"/><w:color w:val="auto"/></w:rPr><w:t>Perpetual License</w:t></w:r></w:p>
        </w:tc>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="1500" w:type="dxa"/>
            <w:tcMar><w:top w:val="140"/><w:left w:val="160"/><w:bottom w:val="140"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p>
            <w:jc w:val="right"/>
            <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="20"/><w:color w:val="auto"/></w:rPr><w:t>FREE</w:t></w:r>
          </w:p>
          <w:p>
            <w:jc w:val="right"/>
            <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="17"/><w:color w:val="auto"/></w:rPr><w:t>(Apache 2.0)</w:t></w:r>
          </w:p>
        </w:tc>
      </w:tr>

      <!-- Row 5: Total -->
      <w:tr>
        <w:trPr>
          <w:trHeight w:val="550"/>
        </w:trPr>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="2500" w:type="dxa"/>
            <w:tcMar><w:top w:val="160"/><w:left w:val="160"/><w:bottom w:val="160"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p>
            <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>TOTAL ESTIMATED COST</w:t></w:r>
          </w:p>
        </w:tc>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="5800" w:type="dxa"/>
            <w:gridSpan w:val="2"/>
            <w:tcMar><w:top w:val="160"/><w:left w:val="160"/><w:bottom w:val="160"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p>
            <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:i/><w:sz w:val="19"/><w:color w:val="auto"/></w:rPr><w:t>Covers complete production hosting, domain routing, and unlimited blockchain transactions for one full operational year.</w:t></w:r>
          </w:p>
        </w:tc>
        <w:tc>
          <w:tcPr>
            <w:tcW w:w="1500" w:type="dxa"/>
            <w:tcMar><w:top w:val="160"/><w:left w:val="160"/><w:bottom w:val="160"/><w:right w:val="160"/></w:tcMar>
          </w:tcPr>
          <w:p>
            <w:jc w:val="right"/>
            <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="22"/><w:color w:val="auto"/></w:rPr><w:t>PHP 8,000.00</w:t></w:r>
          </w:p>
          <w:p>
            <w:jc w:val="right"/>
            <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="17"/><w:color w:val="auto"/></w:rPr><w:t>(Estimated range: ₱7,388 – ₱8,800)</w:t></w:r>
          </w:p>
        </w:tc>
      </w:tr>
    </w:tbl>

    <w:p><w:pPr><w:spacing w:after="360"/></w:pPr></w:p>

    <!-- Section 1 -->
    <w:p>
      <w:pPr><w:spacing w:before="360" w:after="140"/></w:pPr>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="24"/><w:color w:val="auto"/></w:rPr><w:t>1. Current Pricing &amp; Market Rate Validation (Hostinger KVM VPS)</w:t></w:r>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="160"/></w:pPr>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="22"/><w:color w:val="auto"/></w:rPr><w:t>A detailed market rate analysis of Hostinger’s official Philippine VPS hosting reveals that Kernel-based Virtual Machine (KVM) VPS plans provide dedicated resources, root shell access, and Docker support:</w:t></w:r>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="120"/><w:ind w:left="400"/></w:pPr>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>• Hostinger KVM 2 Plan (Recommended Baseline): </w:t></w:r>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>Offers 2 vCPU cores, 8 GB RAM, 100 GB NVMe storage, and 8 TB bandwidth. Promoted at approximately ₱549.00/month on introductory terms, with a standard annual budget allocation between ₱6,588.00 and ₱7,200.00/year.</w:t></w:r>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="120"/><w:ind w:left="400"/></w:pPr>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>• Hostinger KVM 4 Plan (Upper-Tier Scale): </w:t></w:r>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>Offers 4 vCPU cores, 16 GB RAM, 200 GB NVMe storage, and 16 TB bandwidth at approximately ₱749.00/month (~₱8,988.00/year). Suitable if the campus hosts multi-node validators across multiple physical departments.</w:t></w:r>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="160"/><w:ind w:left="400"/></w:pPr>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>• Domain Name Registration: </w:t></w:r>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>Annual registration for a standard commercial top-level domain (.com or .org) costs approximately ₱650.00 to ₱800.00/year. Alternatively, if routed as a university institutional subdomain (e.g. orgchain.batstate-u.edu.ph) through the BatStateU ICT Services Office, the incremental cost is ₱0.00.</w:t></w:r>
    </w:p>

    <!-- Section 2 -->
    <w:p>
      <w:pPr><w:spacing w:before="360" w:after="140"/></w:pPr>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="24"/><w:color w:val="auto"/></w:rPr><w:t>2. Technical Justification: VPS vs. Shared Web Hosting</w:t></w:r>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="160"/></w:pPr>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="22"/><w:color w:val="auto"/></w:rPr><w:t>Standard shared web hosting plans (such as cPanel "Business Web Hosting") are technically incapable of hosting OrgChain. The architectural justifications requiring a Virtual Private Server (VPS) include:</w:t></w:r>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="120"/><w:ind w:left="400"/></w:pPr>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>1. Persistent Process Lifecycles: </w:t></w:r>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>Shared web hosts kill long-running background processes after 60 to 120 seconds. Hyperledger Besu validator nodes must run continuously 24/7 as background daemons to maintain block consensus.</w:t></w:r>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="120"/><w:ind w:left="400"/></w:pPr>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>2. Containerization Engine (Docker &amp; Docker Compose): </w:t></w:r>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>OrgChain deploys its multi-validator blockchain network inside Docker containers. Shared hosting environments strictly forbid Docker execution and root shell privileges.</w:t></w:r>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="160"/><w:ind w:left="400"/></w:pPr>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>3. Custom Port Listening &amp; P2P Sockets: </w:t></w:r>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>Shared hosting environments restrict incoming traffic to ports 80 and 443. Hyperledger Besu requires custom ports for JSON-RPC bridges (port 8545) and inter-node discovery protocols (port 30303), which are fully configurable only on a VPS.</w:t></w:r>
    </w:p>

    <!-- Section 3 -->
    <w:p>
      <w:pPr><w:spacing w:before="360" w:after="140"/></w:pPr>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="24"/><w:color w:val="auto"/></w:rPr><w:t>3. Enterprise Blockchain Architecture: Why Hyperledger Besu is Free</w:t></w:r>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="160"/></w:pPr>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="22"/><w:color w:val="auto"/></w:rPr><w:t>The enterprise blockchain infrastructure operates with zero licensing costs and zero recurring transaction overhead under the following architecture:</w:t></w:r>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="120"/><w:ind w:left="400"/></w:pPr>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>• Backed by The Linux Foundation (Apache 2.0 License): </w:t></w:r>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>Hyperledger Besu is an open-source enterprise Ethereum client created and maintained under the Linux Foundation (collaboratively engineered by ConsenSys, IBM, and major tech institutions). It carries no proprietary seat licenses, subscription tiers, or developer fees.</w:t></w:r>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="120"/><w:ind w:left="400"/></w:pPr>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>• Zero-Gas Economic Architecture (gas_price = 0): </w:t></w:r>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>Public blockchains (such as Ethereum or Polygon) demand variable gas fees in volatile cryptocurrency (ETH/MATIC) to compensate anonymous public miners. Because OrgChain deploys a private permissioned consortium network utilizing QBFT consensus, the university provisions its own validator nodes. In genesis.json, gas fees are configured to zero, enabling millions of student votes and expense audit anchors to execute perpetually at ₱0.00 marginal cost.</w:t></w:r>
    </w:p>

    <w:p>
      <w:pPr><w:spacing w:after="160"/><w:ind w:left="400"/></w:pPr>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>• Data Privacy Compliance (RA 10173): </w:t></w:r>
      <w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="21"/><w:color w:val="auto"/></w:rPr><w:t>State universities are legally bounded by the Philippine Data Privacy Act of 2012. Anchoring student voting telemetry or organization financial vouchers onto a public blockchain exposes internal records globally. Hosting our permissioned ledger internally on the VPS guarantees that transaction data remains strictly within university boundaries.</w:t></w:r>
    </w:p>

    <!-- Page Setup (Letter size, 1-inch margins) -->
    <w:sectPr>
      <w:pgSz w:w="12240" w:h="15840"/>
      <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720"/>
    </w:sectPr>

  </w:body>
</w:document>';

// Standard OpenXML boilerplate
$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
  <Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>
  <Override PartName="/word/webSettings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.webSettings+xml"/>
</Types>';

$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';

$documentRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/webSettings" Target="webSettings.xml"/>
</Relationships>';

$settings = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"
            xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml">
  <w:compat>
    <w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/>
  </w:compat>
</w:settings>';

$webSettings = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:webSettings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:optimizeForBrowser/>
</w:webSettings>';

$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault>
      <w:rPr>
        <w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/>
        <w:sz w:val="22"/>
        <w:szCs w:val="22"/>
        <w:color w:val="auto"/>
      </w:rPr>
    </w:rPrDefault>
  </w:docDefaults>
</w:styles>';

// Generate the ZIP / DOCX package
$zip = new ZipArchive();
$tempFile = tempnam(sys_get_temp_dir(), 'docx_costing') . '.docx';
@unlink($tempFile);

if ($zip->open($tempFile, ZipArchive::CREATE) !== true) {
    die("Failed to create docx temp file\n");
}

$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('_rels/.rels', $rels);
$zip->addFromString('word/_rels/document.xml.rels', $documentRels);
$zip->addFromString('word/document.xml', $documentXml);
$zip->addFromString('word/styles.xml', $styles);
$zip->addFromString('word/settings.xml', $settings);
$zip->addFromString('word/webSettings.xml', $webSettings);
$zip->close();

if (!copy($tempFile, $outputPath)) {
    echo "Warning: Could not overwrite $outputPath (it may be open in Word!)\n";
}
if (!copy($tempFile, $publicPath)) {
    echo "Warning: Could not overwrite $publicPath (it may be open in Word!)\n";
}
@unlink($tempFile);

echo "DOCX created successfully at:\n" . realpath($outputPath) . "\n";
echo "Public copy created at:\n" . realpath($publicPath) . "\n";
