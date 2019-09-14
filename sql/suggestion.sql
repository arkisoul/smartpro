/****** Object:  Table [dbo].[Suggestions]    Script Date: 12/19/2017 06:39:19 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[Suggestions](
  [Id] [int] IDENTITY(1,1) NOT NULL,
  [CustomerID] [nvarchar] (200) NULL,
  [ProjectID] [nvarchar] (200) NULL,
  [Suggestion] [nvarchar] (max) NULL,
  [CreateDate] [datetime2] (7) NULL,
 CONSTRAINT [PK_Suggestions] PRIMARY KEY CLUSTERED
(
  [Id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO
