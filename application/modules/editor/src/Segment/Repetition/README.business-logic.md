# Repetition Business Logic description

| MetaInfo     |                             |
|--------------|-----------------------------|
| **Status:**  | Draft / Incomplete          |
| **Type:**    | business logic description  |
| **Context:** | Repetitions, Segment saving |

## Terminology
- Master Segment: The segment which was opened for editing by the user
- Repetition: A segment with the same content to the Master Segment

## Duration / Post editing time recording
- The editing time of the master segment is recorded as usual: The time the user spends in editing the segment
- Repetitons:
  - If the manual repetition editor is used the average time per repeated segment is used:
    Time spent in repetition editor / count of repetitions processed
  - If the automatic repetition editor is used (Always replace automatically and set status):
    0 per each repetition - so only the duration in master segment is recorded