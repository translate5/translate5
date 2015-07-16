SetLocal DisableDelayedExpansion
Set "Line="
For /F %%# In ('"Prompt;$H & For %%# in (1) Do Rem"') Do (
    Set "BS=%%#"
)

:loop_start
    Set "Key="
    For /F "delims=" %%# In ('Xcopy /L /W "%~f0" "%~f0" 2^>Nul') Do (
        If Not Defined Key (
            Set "Key=%%#"
        )
    )
    Set "Key=%Key:~-1%"
    SetLocal EnableDelayedExpansion
    If Not Defined Key (
        Goto :loop_end
    )
    If %BS%==^%Key% (
        Set "Key="
        If Defined Line (
            Set "Line=!Line:~0,-1!"
        )
    )
    If Not Defined Line (
        EndLocal
        Set "Line=%Key%"
    ) Else (
        For /F "delims=" %%# In ("!Line!") Do (
            EndLocal
            Set "Line=%%#%Key%"
        )
    )
    Goto :loop_start
:loop_end

Echo;!Line!