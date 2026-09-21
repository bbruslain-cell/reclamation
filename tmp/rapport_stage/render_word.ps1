param(
    [Parameter(Mandatory = $true)][string]$InputDocx,
    [Parameter(Mandatory = $true)][string]$OutputPdf,
    [switch]$UpdateFields,
    [switch]$SaveAfterUpdate
)

$word = $null
$doc = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $doc = $word.Documents.Open($InputDocx, $false, -not $SaveAfterUpdate.IsPresent)
    if ($UpdateFields.IsPresent) {
        foreach ($toc in $doc.TablesOfContents) {
            [void]$toc.Update()
        }
        foreach ($story in $doc.StoryRanges) {
            $current = $story
            while ($null -ne $current) {
                if ($current.Fields.Count -gt 0) { [void]$current.Fields.Update() }
                $current = $current.NextStoryRange
            }
        }
    }
    $pages = $doc.ComputeStatistics(2)
    if ($SaveAfterUpdate.IsPresent) { $doc.Save() }
    $doc.ExportAsFixedFormat($OutputPdf, 17)
    Write-Output "PAGES=$pages"
    Get-Item -LiteralPath $OutputPdf | Select-Object FullName,Length
}
finally {
    if ($null -ne $doc) {
        try { $doc.Close($false) } catch {}
        try { [void][Runtime.InteropServices.Marshal]::FinalReleaseComObject($doc) } catch {}
    }
    if ($null -ne $word) {
        try { $word.Quit() } catch {}
        try { [void][Runtime.InteropServices.Marshal]::FinalReleaseComObject($word) } catch {}
    }
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}
